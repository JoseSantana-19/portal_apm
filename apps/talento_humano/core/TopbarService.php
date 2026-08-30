<?php
declare(strict_types=1);

final class TopbarService
{
    private static ?array $cache = null;

    public static function context(array $authUser): array
    {
        if (self::$cache !== null) return self::$cache;

        $name = trim((string)($authUser['name'] ?? 'Usuario Talento Humano'));
        $context = [
            'name' => $name !== '' ? $name : 'Usuario Talento Humano',
            'role' => (string)($authUser['role'] ?? 'APM'),
            'email' => '',
            'identification' => '',
            'photo' => 'public/img/default_avatar.png',
            'initials' => self::initials($name),
            'notifications' => [],
        ];

        $userId = (int)($authUser['sub'] ?? 0);
        if ($userId <= 0 || !class_exists(Conexion::class)) return self::$cache = $context;

        try {
            $db = Conexion::conectar();
            $profile = $db->prepare(
                "SELECT TOP 1 u.nombre,u.correo,u.mfa_habilitado,e.identificacion,e.ruta_foto
                 FROM dbo.th_usuarios_sistema u
                 LEFT JOIN dbo.th_empleados e ON e.empleado_id=u.empleado_id
                 WHERE u.usuario_id=:id"
            );
            $profile->execute([':id'=>$userId]);
            $row = $profile->fetch(PDO::FETCH_ASSOC) ?: [];
            if (trim((string)($row['nombre'] ?? '')) !== '') $context['name'] = trim((string)$row['nombre']);
            $context['role'] = (string)($authUser['role'] ?? $context['role']);
            $context['email'] = trim((string)($row['correo'] ?? ''));
            $context['identification'] = trim((string)($row['identificacion'] ?? ''));
            $context['photo'] = self::safePhoto((string)($row['ruta_foto'] ?? ''));
            $context['initials'] = self::initials($context['name']);

            if (!empty($authUser['password_change_required'])) {
                $context['notifications'][] = self::notification('password','warning','bi-key-fill','Cambio de contraseña pendiente','Actualice la clave temporal de su cuenta.','/cuenta/cambiar-clave');
            }
            if (empty($row['mfa_habilitado'])) {
                $context['notifications'][] = self::notification('mfa','warning','bi-shield-exclamation','Proteja su cuenta','Active la autenticación en dos pasos.','/cuenta/seguridad');
            }

            $birthdayQuery = $db->prepare(
                "DECLARE @fecha date=CONVERT(date,:fecha);
                 SELECT COUNT_BIG(*) FROM dbo.th_empleados
                 WHERE estado=1 AND fecha_nacimiento IS NOT NULL
                   AND MONTH(fecha_nacimiento)=MONTH(@fecha) AND DAY(fecha_nacimiento)=DAY(@fecha)"
            );
            $birthdayQuery->execute([':fecha'=>InstitutionalClock::todayIso()]);
            $birthdays = (int)$birthdayQuery->fetchColumn();
            if ($birthdays > 0) {
                $context['notifications'][] = self::notification('birthdays','info','bi-cake2-fill','Cumpleaños de hoy',self::plural($birthdays,'funcionario cumple','funcionarios cumplen').' años hoy.','/talento-humano/inicio#seccion-cumpleanos');
            }

            if (Auth::can('acciones','visualizar')) {
                $pending = (int)$db->query("SELECT COUNT_BIG(*) FROM dbo.th_acciones_personal WHERE UPPER(estado_documento) IN ('BORRADOR','PENDIENTE')")->fetchColumn();
                if ($pending > 0) {
                    $context['notifications'][] = self::notification('actions','action','bi-file-earmark-text-fill','Acciones por revisar',self::plural($pending,'documento pendiente','documentos pendientes').' de revisión.','/talento-humano/accion-personal');
                }
                if (self::objectExists($db, 'dbo.th_vigencias_laborales')) {
                    $expiring = $db->prepare(
                        "DECLARE @hoy date=CONVERT(date,:fecha);
                         SELECT COUNT_BIG(*) FROM (
                           SELECT vigencia_id id FROM dbo.th_vigencias_laborales
                           WHERE estado IN('PROGRAMADA','VIGENTE') AND fecha_hasta BETWEEN @hoy AND DATEADD(DAY,7,@hoy)
                           UNION ALL
                           SELECT jornada_especial_id FROM dbo.th_jornadas_especiales
                           WHERE estado IN('PROGRAMADA','VIGENTE') AND fecha_hasta BETWEEN @hoy AND DATEADD(DAY,7,@hoy)
                         ) proximas"
                    );
                    $expiring->execute([':fecha'=>InstitutionalClock::todayIso()]);
                    $expiringCount = (int)$expiring->fetchColumn();
                    if ($expiringCount > 0) {
                        $context['notifications'][] = self::notification(
                            'expiring-vigencies','warning','bi-arrow-counterclockwise','Retornos automáticos próximos',
                            self::plural($expiringCount,'vigencia finaliza','vigencias finalizan').' en los próximos 7 días.',
                            '/talento-humano/reporte'
                        );
                    }
                }
            }

            if (Auth::can('auditoria','visualizar')) {
                $alerts = (int)$db->query(
                    "SELECT COUNT_BIG(*) FROM dbo.th_logs_auditoria
                     WHERE fecha_hora>=DATEADD(HOUR,-24,SYSDATETIME())
                       AND (accion LIKE '%FALLIDO%' OR accion IN ('ACCESO_DENEGADO','MFA_ERROR'))"
                )->fetchColumn();
                if ($alerts > 0) {
                    $context['notifications'][] = self::notification('audit','danger','bi-exclamation-triangle-fill','Alertas de seguridad',self::plural($alerts,'evento requiere','eventos requieren').' revisión en las últimas 24 horas.','/auditoria/reportes');
                }
            }
        } catch (Exception $error) {
            Conexion::registrarErrorLog($error,'Topbar',false);
        }

        return self::$cache = $context;
    }

    private static function notification(string $id,string $tone,string $icon,string $title,string $text,string $url): array
    {
        return compact('id','tone','icon','title','text','url');
    }

    private static function objectExists(PDO $db, string $qualifiedName): bool
    {
        $query = $db->prepare('SELECT IIF(OBJECT_ID(:name) IS NULL,0,1)');
        $query->execute([':name'=>$qualifiedName]);
        return (int)$query->fetchColumn() === 1;
    }

    private static function safePhoto(string $path): string
    {
        $path = ltrim(str_replace('\\','/',$path),'/');
        if ($path !== '' && str_starts_with($path,'public/img/') && is_file(ROOT.'/'.$path)) return $path;
        return 'public/img/default_avatar.png';
    }

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u',trim($name),-1,PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach(array_slice($parts,0,2) as $part) $initials .= mb_strtoupper(mb_substr($part,0,1));
        return $initials !== '' ? $initials : 'AP';
    }

    private static function plural(int $count,string $singular,string $plural): string
    {
        return $count.' '.($count===1?$singular:$plural);
    }
}
