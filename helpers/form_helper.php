<?php
class FormHelper {

    private static array $errors = [];

    /** Validate rules. Returns true if all pass. */
    public static function validate(array $data, array $rules): bool {
        self::$errors = [];
        foreach ($rules as $field => $ruleStr) {
            $val   = $data[$field] ?? '';
            $rules_ = explode('|', $ruleStr);
            foreach ($rules_ as $rule) {
                [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $error = self::applyRule($ruleName, $field, $val, $param, $data);
                if ($error) {
                    self::$errors[$field] = $error;
                    break;
                }
            }
        }
        return empty(self::$errors);
    }

    private static function applyRule(string $rule, string $field, $val, ?string $param, array $data): ?string {
        switch ($rule) {
            case 'required':
                return (trim((string)$val) === '') ? "El campo {$field} es requerido." : null;
            case 'min':
                return (mb_strlen((string)$val) < (int)$param) ? "Mínimo {$param} caracteres." : null;
            case 'max':
                return (mb_strlen((string)$val) > (int)$param) ? "Máximo {$param} caracteres." : null;
            case 'email':
                return (!filter_var($val, FILTER_VALIDATE_EMAIL)) ? "Correo inválido." : null;
            case 'numeric':
                return (!is_numeric($val)) ? "Debe ser numérico." : null;
            case 'in':
                return (!in_array($val, explode(',', $param ?? ''), true)) ? "Valor no permitido." : null;
            case 'confirmed':
                return ($val !== ($data[$field . '_confirmation'] ?? '')) ? "Los campos no coinciden." : null;
            case 'alpha_num':
                return (!ctype_alnum(str_replace(['_','-'], '', (string)$val))) ? "Solo letras, números, _ y -." : null;
            default:
                return null;
        }
    }

    public static function errors(): array   { return self::$errors; }
    public static function hasErrors(): bool { return !empty(self::$errors); }
    public static function error(string $field): ?string { return self::$errors[$field] ?? null; }

    /** Render error span for a field. */
    public static function errorHtml(string $field): string {
        $e = self::$errors[$field] ?? null;
        return $e ? '<span class="form-error">' . SecurityHelper::e($e) . '</span>' : '';
    }

    /** Old input value (after validation failure). */
    public static function old(string $key, $default = '') {
        return SessionHelper::getFlash("old_{$key}") ?? $default;
    }

    /** Store old input on redirect. */
    public static function flashOld(array $data): void {
        foreach ($data as $k => $v) {
            SessionHelper::flash("old_{$k}", $v);
        }
    }
}
