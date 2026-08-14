<?php

namespace Modules\Ebook\Services;

class SyntaxHighlighterService
{
    public function highlight(string $escapedCode, string $language): string
    {
        $language = strtolower(trim($language));
        $code = html_entity_decode($escapedCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($language === '' || ! $this->supports($language)) {
            return e($code);
        }

        $keywords = $this->keywords($language);
        $keywordPattern = $keywords === []
            ? '(?!)'
            : '\\b(?:'.implode('|', array_map(static fn (string $keyword): string => preg_quote($keyword, '/'), $keywords)).')\\b';

        $pattern = '/(?<comment>\/\/[^\r\n]*|#[^\r\n]*|\/\*[\s\S]*?\*\/|<!--[\s\S]*?-->)'
            .'|(?<string>"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')'
            .'|(?<number>\b(?:0x[0-9a-f]+|\d+(?:\.\d+)?)\b)'
            .'|(?<keyword>'.$keywordPattern.')/i';

        $offset = 0;
        $html = '';
        $classes = [
            'comment' => 'ebook-syntax-comment text-slate-500 italic',
            'string' => 'ebook-syntax-string text-emerald-300',
            'number' => 'ebook-syntax-number text-amber-300',
            'keyword' => 'ebook-syntax-keyword font-semibold text-fuchsia-300',
        ];

        preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            $text = $match[0][0];
            $position = $match[0][1];
            $html .= e(substr($code, $offset, $position - $offset));

            $type = 'keyword';
            foreach (['comment', 'string', 'number', 'keyword'] as $candidate) {
                if (isset($match[$candidate]) && $match[$candidate][1] !== -1) {
                    $type = $candidate;
                    break;
                }
            }

            $html .= '<span class="'.$classes[$type].'">'.e($text).'</span>';
            $offset = $position + strlen($text);
        }

        return $html.e(substr($code, $offset));
    }

    private function supports(string $language): bool
    {
        return array_key_exists($language, $this->languageKeywords());
    }

    private function keywords(string $language): array
    {
        return $this->languageKeywords()[$language] ?? [];
    }

    private function languageKeywords(): array
    {
        $javascript = ['async', 'await', 'break', 'case', 'catch', 'class', 'const', 'continue', 'default', 'delete', 'do', 'else', 'export', 'extends', 'false', 'finally', 'for', 'from', 'function', 'if', 'import', 'in', 'instanceof', 'let', 'new', 'null', 'of', 'return', 'static', 'super', 'switch', 'this', 'throw', 'true', 'try', 'typeof', 'undefined', 'var', 'while', 'yield'];
        $typescript = array_merge($javascript, ['any', 'boolean', 'enum', 'implements', 'interface', 'keyof', 'namespace', 'never', 'number', 'private', 'protected', 'public', 'readonly', 'string', 'type', 'unknown', 'void']);

        return [
            'php' => ['abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'enum', 'extends', 'false', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'if', 'implements', 'include', 'include_once', 'instanceof', 'interface', 'isset', 'match', 'namespace', 'new', 'null', 'or', 'print', 'private', 'protected', 'public', 'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'true', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'],
            'js' => $javascript,
            'javascript' => $javascript,
            'ts' => $typescript,
            'typescript' => $typescript,
            'sql' => ['alter', 'and', 'as', 'asc', 'between', 'by', 'case', 'create', 'delete', 'desc', 'distinct', 'drop', 'else', 'end', 'exists', 'from', 'group', 'having', 'in', 'index', 'inner', 'insert', 'into', 'is', 'join', 'left', 'like', 'limit', 'not', 'null', 'on', 'or', 'order', 'outer', 'right', 'select', 'set', 'table', 'then', 'union', 'update', 'values', 'when', 'where'],
            'json' => ['false', 'null', 'true'],
            'bash' => ['case', 'do', 'done', 'elif', 'else', 'esac', 'export', 'fi', 'for', 'function', 'if', 'in', 'local', 'readonly', 'return', 'then', 'until', 'while'],
            'shell' => ['case', 'do', 'done', 'elif', 'else', 'esac', 'export', 'fi', 'for', 'function', 'if', 'in', 'local', 'readonly', 'return', 'then', 'until', 'while'],
            'sh' => ['case', 'do', 'done', 'elif', 'else', 'esac', 'export', 'fi', 'for', 'function', 'if', 'in', 'local', 'readonly', 'return', 'then', 'until', 'while'],
            'css' => ['import', 'media', 'supports', 'important'],
            'html' => ['doctype', 'html', 'head', 'body', 'script', 'style', 'div', 'span', 'a', 'img', 'table', 'form', 'input', 'button'],
            'yaml' => ['false', 'null', 'true'],
            'yml' => ['false', 'null', 'true'],
            'dockerfile' => ['add', 'arg', 'cmd', 'copy', 'entrypoint', 'env', 'expose', 'from', 'healthcheck', 'label', 'run', 'shell', 'stopsignal', 'user', 'volume', 'workdir'],
            'nginx' => ['events', 'http', 'include', 'listen', 'location', 'proxy_pass', 'return', 'server', 'server_name', 'upstream'],
            'md' => [],
            'markdown' => [],
        ];
    }
}
