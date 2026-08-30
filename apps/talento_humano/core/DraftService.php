<?php
declare(strict_types=1);

final class DraftService
{
    private const CIPHER = 'aes-256-gcm';

    public static function normalizeContext(string $context): string
    {
        $context = trim($context);
        if ($context === '' || strlen($context) > 180 || !preg_match('#^[a-zA-Z0-9._:/-]+$#', $context)) {
            throw new InvalidArgumentException('El contexto del borrador no es valido.');
        }
        return $context;
    }

    public static function save(int $userId, string $context, array $fields): void
    {
        $context = self::normalizeContext($context);
        $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 1024 * 1024) throw new LengthException('El borrador supera el tamano permitido.');
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($json, self::CIPHER, Config::draftKey(), OPENSSL_RAW_DATA, $iv, $tag, $context, 16);
        if ($ciphertext === false) throw new RuntimeException('No fue posible cifrar el borrador.');

        $stmt = Conexion::conectar()->prepare('EXEC dbo.sp_th_guardar_borrador :usuario,:contexto,:payload,:iv,:tag');
        $stmt->execute([
            ':usuario'=>$userId, ':contexto'=>$context, ':payload'=>base64_encode($ciphertext),
            ':iv'=>base64_encode($iv), ':tag'=>base64_encode($tag),
        ]);
    }

    public static function load(int $userId, string $context): ?array
    {
        $context = self::normalizeContext($context);
        $stmt = Conexion::conectar()->prepare('EXEC dbo.sp_th_obtener_borrador :usuario,:contexto');
        $stmt->execute([':usuario'=>$userId, ':contexto'=>$context]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $plain = openssl_decrypt(
            base64_decode((string)$row['payload_cifrado'], true) ?: '', self::CIPHER, Config::draftKey(), OPENSSL_RAW_DATA,
            base64_decode((string)$row['iv'], true) ?: '', base64_decode((string)$row['tag_auth'], true) ?: '', $context
        );
        if ($plain === false) throw new RuntimeException('El borrador no pudo ser validado.');
        $fields = json_decode($plain, true, 64, JSON_THROW_ON_ERROR);
        return ['fields'=>is_array($fields)?$fields:[], 'updated_at'=>(string)($row['fecha_actualizacion']??'')];
    }

    public static function delete(int $userId, string $context): void
    {
        $context = self::normalizeContext($context);
        $stmt = Conexion::conectar()->prepare('EXEC dbo.sp_th_eliminar_borrador :usuario,:contexto');
        $stmt->execute([':usuario'=>$userId, ':contexto'=>$context]);
    }

    public static function deleteCurrent(string $context): void
    {
        $user = Auth::user();
        if ($user && trim($context) !== '') self::delete((int)$user['sub'], $context);
    }
}
