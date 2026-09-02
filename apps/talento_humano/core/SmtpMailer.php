<?php

final class SmtpMailer
{
    /** @var resource|null */
    private $socket = null;

    /**
     * Envía una notificación de texto mediante el relay institucional.
     *
     * @param list<string> $recipients
     * @return array{sent:bool,skipped:bool,message:string,recipients:int}
     */
    public function send(array $recipients, string $subject, string $body): array
    {
        $config = Config::smtp();
        $recipients = self::normalizeRecipients($recipients);
        if (!$config['enabled']) {
            return ['sent'=>false, 'skipped'=>true, 'message'=>'El transporte SMTP no está habilitado.', 'recipients'=>count($recipients)];
        }
        if (!$recipients) {
            throw new InvalidArgumentException('No existen destinatarios SMTP válidos.');
        }

        try {
            $this->connect($config);
            $this->command('EHLO portal-apm', [250]);
            if ($config['encryption'] === 'tls') {
                $this->command('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No fue posible establecer el canal TLS con el relay SMTP.');
                }
                $this->command('EHLO portal-apm', [250]);
            }
            if ($config['username'] !== '') {
                $this->command('AUTH LOGIN', [334]);
                $this->command(base64_encode($config['username']), [334], true);
                $this->command(base64_encode($config['password']), [235], true);
            }

            $this->command('MAIL FROM:<'.$config['from_address'].'>', [250]);
            foreach ($recipients as $recipient) {
                $this->command('RCPT TO:<'.$recipient.'>', [250, 251]);
            }
            $this->command('DATA', [354]);
            $message = $this->buildMessage($config, $recipients, $subject, $body);
            $this->write($message."\r\n.\r\n");
            $this->expect([250]);
            $this->command('QUIT', [221]);

            return ['sent'=>true, 'skipped'=>false, 'message'=>'Notificación SMTP enviada.', 'recipients'=>count($recipients)];
        } finally {
            if (is_resource($this->socket)) fclose($this->socket);
            $this->socket = null;
        }
    }

    /** @param array<int|string,mixed> $values @return list<string> */
    public static function normalizeRecipients(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            foreach (preg_split('/[\s,;]+/', mb_strtolower(trim((string)$value), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('La lista contiene un destinatario SMTP inválido.');
                }
                $result[$email] = $email;
            }
        }
        return array_values($result);
    }

    /** @param array<string,mixed> $config */
    private function connect(array $config): void
    {
        $context = stream_context_create(['ssl'=>[
            'verify_peer'=>(bool)$config['verify_peer'],
            'verify_peer_name'=>(bool)$config['verify_peer'],
            'allow_self_signed'=>false,
            'peer_name'=>$config['host'],
            'SNI_enabled'=>true,
        ]]);
        $prefix = $config['encryption'] === 'ssl' ? 'ssl://' : 'tcp://';
        $errno = 0;
        $error = '';
        $this->socket = @stream_socket_client(
            $prefix.$config['host'].':'.$config['port'],
            $errno,
            $error,
            (float)$config['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($this->socket)) {
            throw new RuntimeException('No fue posible conectar con el relay SMTP institucional.');
        }
        stream_set_timeout($this->socket, (int)$config['timeout']);
        $this->expect([220]);
    }

    /** @param list<int> $expected */
    private function command(string $command, array $expected, bool $sensitive = false): void
    {
        $this->write($command."\r\n");
        try {
            $this->expect($expected);
        } catch (Throwable $error) {
            if ($sensitive) throw new RuntimeException('El relay SMTP rechazó la autenticación.', 0, $error);
            throw $error;
        }
    }

    private function write(string $value): void
    {
        if (!is_resource($this->socket) || fwrite($this->socket, $value) === false) {
            throw new RuntimeException('Se interrumpió la comunicación con el relay SMTP.');
        }
    }

    /** @param list<int> $expected */
    private function expect(array $expected): void
    {
        if (!is_resource($this->socket)) throw new RuntimeException('La conexión SMTP no está disponible.');
        $response = '';
        do {
            $line = fgets($this->socket, 1024);
            if ($line === false) throw new RuntimeException('El relay SMTP cerró la conexión inesperadamente.');
            $response .= $line;
        } while (strlen($line) >= 4 && $line[3] === '-');

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('El relay SMTP rechazó la operación (código '.$code.').');
        }
    }

    /** @param array<string,mixed> $config @param list<string> $recipients */
    private function buildMessage(array $config, array $recipients, string $subject, string $body): string
    {
        $cleanSubject = trim(str_replace(["\r", "\n"], ' ', $subject));
        $cleanName = trim(str_replace(["\r", "\n"], ' ', (string)$config['from_name']));
        $encodedSubject = mb_encode_mimeheader($cleanSubject, 'UTF-8', 'B', "\r\n");
        $encodedName = mb_encode_mimeheader($cleanName, 'UTF-8', 'B', "\r\n");
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", trim($body));
        $normalizedBody = preg_replace('/^\./m', '..', $normalizedBody) ?? $normalizedBody;

        return implode("\r\n", [
            'Date: '.date(DATE_RFC2822),
            'From: '.$encodedName.' <'.$config['from_address'].'>',
            'To: '.implode(', ', $recipients),
            'Subject: '.$encodedSubject,
            'Message-ID: <'.bin2hex(random_bytes(12)).'@portal-apm>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            'Auto-Submitted: auto-generated',
            '',
            quoted_printable_encode($normalizedBody),
        ]);
    }
}
