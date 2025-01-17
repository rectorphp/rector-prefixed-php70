<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\PhpGenerator;

use RectorPrefix20210620\_HumbugBox15516bb2b566\Nette;
/**
 * PHP code generator utils.
 */
final class Dumper
{
    const INDENT_LENGTH = 4;
    /** @var int */
    public $maxDepth = 50;
    /** @var int */
    public $wrapLength = 120;
    /**
     * Returns a PHP representation of a variable.
     */
    public function dump($var, int $column = 0) : string
    {
        return $this->dumpVar($var, [], 0, $column);
    }
    private function dumpVar(&$var, array $parents = [], int $level = 0, int $column = 0) : string
    {
        if ($var instanceof \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\PhpGenerator\Literal) {
            return \ltrim(\RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\Utils\Strings::indent(\trim((string) $var), $level), "\t");
        } elseif ($var === null) {
            return 'null';
        } elseif (\is_string($var)) {
            return $this->dumpString($var);
        } elseif (\is_array($var)) {
            return $this->dumpArray($var, $parents, $level, $column);
        } elseif (\is_object($var)) {
            return $this->dumpObject($var, $parents, $level);
        } elseif (\is_resource($var)) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Cannot dump resource.');
        } else {
            return \var_export($var, \true);
        }
    }
    private function dumpString(string $var) : string
    {
        if (\preg_match('#[^\\x09\\x20-\\x7E\\xA0-\\x{10FFFF}]#u', $var) || \preg_last_error()) {
            static $table;
            if ($table === null) {
                foreach (\array_merge(\range("\0", "\37"), \range("", "�")) as $ch) {
                    $table[$ch] = '\\x' . \str_pad(\dechex(\ord($ch)), 2, '0', \STR_PAD_LEFT);
                }
                $table['\\'] = '\\\\';
                $table["\r"] = '\\r';
                $table["\n"] = '\\n';
                $table["\t"] = '\\t';
                $table['$'] = '\\$';
                $table['"'] = '\\"';
            }
            return '"' . \strtr($var, $table) . '"';
        }
        return "'" . \preg_replace('#\'|\\\\(?=[\'\\\\]|$)#D', '\\\\$0', $var) . "'";
    }
    private function dumpArray(array &$var, array $parents, int $level, int $column) : string
    {
        if (empty($var)) {
            return '[]';
        } elseif ($level > $this->maxDepth || \in_array($var, $parents ?? [], \true)) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }
        $space = \str_repeat("\t", $level);
        $outInline = '';
        $outWrapped = "\n{$space}";
        $parents[] = $var;
        $counter = 0;
        $hideKeys = \is_int(($tmp = \array_keys($var))[0]) && $tmp === \range($tmp[0], $tmp[0] + \count($var) - 1);
        foreach ($var as $k => &$v) {
            $keyPart = $hideKeys && $k === $counter ? '' : $this->dumpVar($k) . ' => ';
            $counter = \is_int($k) ? \max($k + 1, $counter) : $counter;
            $outInline .= ($outInline === '' ? '' : ', ') . $keyPart;
            $outInline .= $this->dumpVar($v, $parents, 0, $column + \strlen($outInline));
            $outWrapped .= "\t" . $keyPart . $this->dumpVar($v, $parents, $level + 1, \strlen($keyPart)) . ",\n{$space}";
        }
        \array_pop($parents);
        $wrap = \strpos($outInline, "\n") !== \false || $level * self::INDENT_LENGTH + $column + \strlen($outInline) + 3 > $this->wrapLength;
        // 3 = [],
        return '[' . ($wrap ? $outWrapped : $outInline) . ']';
    }
    private function dumpObject(&$var, array $parents, int $level) : string
    {
        if ($var instanceof \Serializable) {
            return 'unserialize(' . $this->dumpString(\serialize($var)) . ')';
        } elseif ($var instanceof \Closure) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Cannot dump closure.');
        }
        $class = \get_class($var);
        if ((new \ReflectionObject($var))->isAnonymous()) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Cannot dump anonymous class.');
        } elseif (\in_array($class, [\DateTime::class, \DateTimeImmutable::class], \true)) {
            return $this->format("new \\{$class}(?, new \\DateTimeZone(?))", $var->format('Y-m-d H:i:s.u'), $var->getTimeZone()->getName());
        }
        $arr = (array) $var;
        $space = \str_repeat("\t", $level);
        if ($level > $this->maxDepth || \in_array($var, $parents ?? [], \true)) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }
        $out = "\n";
        $parents[] = $var;
        if (\method_exists($var, '__sleep')) {
            foreach ($var->__sleep() as $v) {
                $props[$v] = $props["\0*\0{$v}"] = $props["\0{$class}\0{$v}"] = \true;
            }
        }
        foreach ($arr as $k => &$v) {
            if (!isset($props) || isset($props[$k])) {
                $out .= "{$space}\t" . ($keyPart = $this->dumpVar($k) . ' => ') . $this->dumpVar($v, $parents, $level + 1, \strlen($keyPart)) . ",\n";
            }
        }
        \array_pop($parents);
        $out .= $space;
        return $class === \stdClass::class ? "(object) [{$out}]" : '\\' . self::class . "::createObject('{$class}', [{$out}])";
    }
    /**
     * Generates PHP statement.
     */
    public function format(string $statement, ...$args) : string
    {
        $tokens = \preg_split('#(\\.\\.\\.\\?|\\$\\?|->\\?|::\\?|\\\\\\?|\\?\\*|\\?)#', $statement, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $res = '';
        foreach ($tokens as $n => $token) {
            if ($n % 2 === 0) {
                $res .= $token;
            } elseif ($token === '\\?') {
                $res .= '?';
            } elseif (!$args) {
                throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Insufficient number of arguments.');
            } elseif ($token === '?') {
                $res .= $this->dump(\array_shift($args), \strlen($res) - \strrpos($res, "\n"));
            } elseif ($token === '...?' || $token === '?*') {
                $arg = \array_shift($args);
                if (!\is_array($arg)) {
                    throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Argument must be an array.');
                }
                $res .= $this->dumpArguments($arg, \strlen($res) - \strrpos($res, "\n"));
            } else {
                // $  ->  ::
                $arg = \array_shift($args);
                if ($arg instanceof \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\PhpGenerator\Literal || !\RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\PhpGenerator\Helpers::isIdentifier($arg)) {
                    $arg = '{' . $this->dumpVar($arg) . '}';
                }
                $res .= \substr($token, 0, -1) . $arg;
            }
        }
        if ($args) {
            throw new \RectorPrefix20210620\_HumbugBox15516bb2b566\Nette\InvalidArgumentException('Insufficient number of placeholders.');
        }
        return $res;
    }
    private function dumpArguments(array &$var, int $column) : string
    {
        $outInline = $outWrapped = '';
        foreach ($var as $k => &$v) {
            $k = \is_int($k) ? '' : $k . ': ';
            $outInline .= $outInline === '' ? '' : ', ';
            $outInline .= $k . $this->dumpVar($v, [$var], 0, $column + \strlen($outInline));
            $outWrapped .= ($outWrapped === '' ? '' : ',') . "\n\t" . $k . $this->dumpVar($v, [$var], 1);
        }
        return \count($var) > 1 && (\strpos($outInline, "\n") !== \false || $column + \strlen($outInline) > $this->wrapLength) ? $outWrapped . "\n" : $outInline;
    }
    /**
     * @return object
     * @internal
     */
    public static function createObject(string $class, array $props)
    {
        return \unserialize('O' . \substr(\serialize($class), 1, -1) . \substr(\serialize($props), 1));
    }
}
