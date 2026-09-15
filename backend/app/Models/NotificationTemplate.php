<?php

namespace App\Models;

/**
 * Admin-editable notification/email template.
 *
 * Bodies are rendered with whitelisted token substitution only. Template
 * content is never evaluated as PHP.
 */
class NotificationTemplate extends BaseModel
{
    protected $fillable = [
        'key',
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Replace {{ token }} placeholders with provided values.
     *
     * @param  array<string, string|int|float|null>  $values
     */
    public function render(string $content, array $values): string
    {
        $allowed = $this->variables ?? array_keys($values);
        $replacements = [];

        foreach ($allowed as $token) {
            if (array_key_exists($token, $values)) {
                $replacements['{{'.$token.'}}'] = (string) $values[$token];
                $replacements['{{ '.$token.' }}'] = (string) $values[$token];
            }
        }

        return strtr($content, $replacements);
    }
}
