<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator;

use RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette;
use RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\Utils\Strings;
/**
 * Generates PHP code.
 */
class Printer
{
    use Nette\SmartObject;
    /** @var string */
    protected $indentation = "\t";
    /** @var int */
    protected $linesBetweenProperties = 0;
    /** @var int */
    protected $linesBetweenMethods = 2;
    /** @var string */
    protected $returnTypeColon = ': ';
    /** @var bool */
    private $resolveTypes = \true;
    public function printFunction(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\GlobalFunction $function, \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace = null) : string
    {
        return \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment($function->getComment() . "\n") . self::printAttributes($function->getAttributes(), $namespace) . 'function ' . ($function->getReturnReference() ? '&' : '') . $function->getName() . $this->printParameters($function, $namespace) . $this->printReturnType($function, $namespace) . "\n{\n" . $this->indent(\ltrim(\rtrim($function->getBody()) . "\n")) . "}\n";
    }
    public function printClosure(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Closure $closure) : string
    {
        $uses = [];
        foreach ($closure->getUses() as $param) {
            $uses[] = ($param->isReference() ? '&' : '') . '$' . $param->getName();
        }
        $useStr = \strlen($tmp = \implode(', ', $uses)) > (new \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Dumper())->wrapLength && \count($uses) > 1 ? "\n" . $this->indentation . \implode(",\n" . $this->indentation, $uses) . "\n" : $tmp;
        return self::printAttributes($closure->getAttributes(), null, \true) . 'function ' . ($closure->getReturnReference() ? '&' : '') . $this->printParameters($closure, null) . ($uses ? " use ({$useStr})" : '') . $this->printReturnType($closure, null) . " {\n" . $this->indent(\ltrim(\rtrim($closure->getBody()) . "\n")) . '}';
    }
    public function printArrowFunction(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Closure $closure) : string
    {
        foreach ($closure->getUses() as $use) {
            if ($use->isReference()) {
                throw new \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\InvalidArgumentException('Arrow function cannot bind variables by-reference.');
            }
        }
        return self::printAttributes($closure->getAttributes(), null) . 'fn ' . ($closure->getReturnReference() ? '&' : '') . $this->printParameters($closure, null) . $this->printReturnType($closure, null) . ' => ' . \trim($closure->getBody()) . ';';
    }
    public function printMethod(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Method $method, \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace = null) : string
    {
        $method->validate();
        return \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment($method->getComment() . "\n") . self::printAttributes($method->getAttributes(), $namespace) . ($method->isAbstract() ? 'abstract ' : '') . ($method->isFinal() ? 'final ' : '') . ($method->getVisibility() ? $method->getVisibility() . ' ' : '') . ($method->isStatic() ? 'static ' : '') . 'function ' . ($method->getReturnReference() ? '&' : '') . $method->getName() . ($params = $this->printParameters($method, $namespace)) . $this->printReturnType($method, $namespace) . ($method->isAbstract() || $method->getBody() === null ? ";\n" : (\strpos($params, "\n") === \false ? "\n" : ' ') . "{\n" . $this->indent(\ltrim(\rtrim($method->getBody()) . "\n")) . "}\n");
    }
    public function printClass(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\ClassType $class, \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace = null) : string
    {
        $class->validate();
        $resolver = $this->resolveTypes && $namespace ? [$namespace, 'unresolveUnionType'] : function ($s) {
            return $s;
        };
        $traits = [];
        foreach ($class->getTraitResolutions() as $trait => $resolutions) {
            $traits[] = 'use ' . $resolver($trait) . ($resolutions ? " {\n" . $this->indentation . \implode(";\n" . $this->indentation, $resolutions) . ";\n}\n" : ";\n");
        }
        $consts = [];
        foreach ($class->getConstants() as $const) {
            $def = ($const->getVisibility() ? $const->getVisibility() . ' ' : '') . 'const ' . $const->getName() . ' = ';
            $consts[] = \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment((string) $const->getComment()) . self::printAttributes($const->getAttributes(), $namespace) . $def . $this->dump($const->getValue(), \strlen($def)) . ";\n";
        }
        $properties = [];
        foreach ($class->getProperties() as $property) {
            $type = $property->getType();
            $def = ($property->getVisibility() ?: 'public') . ($property->isStatic() ? ' static' : '') . ' ' . \ltrim($this->printType($type, $property->isNullable(), $namespace) . ' ') . '$' . $property->getName();
            $properties[] = \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment((string) $property->getComment()) . self::printAttributes($property->getAttributes(), $namespace) . $def . ($property->getValue() === null && !$property->isInitialized() ? '' : ' = ' . $this->dump($property->getValue(), \strlen($def) + 3)) . ";\n";
        }
        $methods = [];
        foreach ($class->getMethods() as $method) {
            $methods[] = $this->printMethod($method, $namespace);
        }
        $members = \array_filter([\implode('', $traits), $this->joinProperties($consts), $this->joinProperties($properties), ($methods && $properties ? \str_repeat("\n", $this->linesBetweenMethods - 1) : '') . \implode(\str_repeat("\n", $this->linesBetweenMethods), $methods)]);
        return \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\Utils\Strings::normalize(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment($class->getComment() . "\n") . self::printAttributes($class->getAttributes(), $namespace) . ($class->isAbstract() ? 'abstract ' : '') . ($class->isFinal() ? 'final ' : '') . ($class->getName() ? $class->getType() . ' ' . $class->getName() . ' ' : '') . ($class->getExtends() ? 'extends ' . \implode(', ', \array_map($resolver, (array) $class->getExtends())) . ' ' : '') . ($class->getImplements() ? 'implements ' . \implode(', ', \array_map($resolver, $class->getImplements())) . ' ' : '') . ($class->getName() ? "\n" : '') . "{\n" . ($members ? $this->indent(\implode("\n", $members)) : '') . '}') . ($class->getName() ? "\n" : '');
    }
    public function printNamespace(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace) : string
    {
        $name = $namespace->getName();
        $uses = $this->printUses($namespace);
        $classes = [];
        foreach ($namespace->getClasses() as $class) {
            $classes[] = $this->printClass($class, $namespace);
        }
        $body = ($uses ? $uses . "\n\n" : '') . \implode("\n", $classes);
        if ($namespace->hasBracketedSyntax()) {
            return 'namespace' . ($name ? " {$name}" : '') . "\n{\n" . $this->indent($body) . "}\n";
        } else {
            return ($name ? "namespace {$name};\n\n" : '') . $body;
        }
    }
    public function printFile(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpFile $file) : string
    {
        $namespaces = [];
        foreach ($file->getNamespaces() as $namespace) {
            $namespaces[] = $this->printNamespace($namespace);
        }
        return \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\Utils\Strings::normalize("<?php\n" . ($file->getComment() ? "\n" . \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment($file->getComment() . "\n") : '') . "\n" . ($file->hasStrictTypes() ? "declare(strict_types=1);\n\n" : '') . \implode("\n\n", $namespaces)) . "\n";
    }
    /** @return static */
    public function setTypeResolving(bool $state = \true)
    {
        $this->resolveTypes = $state;
        return $this;
    }
    protected function indent(string $s) : string
    {
        $s = \str_replace("\t", $this->indentation, $s);
        return \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\Utils\Strings::indent($s, 1, $this->indentation);
    }
    protected function dump($var, int $column = 0) : string
    {
        return (new \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Dumper())->dump($var, $column);
    }
    protected function printUses(\RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace) : string
    {
        $name = $namespace->getName();
        $uses = [];
        foreach ($namespace->getUses() as $alias => $original) {
            if ($original !== ($name ? $name . '\\' . $alias : $alias)) {
                $uses[] = $alias === $original || \substr($original, -(\strlen($alias) + 1)) === '\\' . $alias ? "use {$original};" : "use {$original} as {$alias};";
            }
        }
        return \implode("\n", $uses);
    }
    /**
     * @param Closure|GlobalFunction|Method  $function
     */
    public function printParameters($function, \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace = null) : string
    {
        $params = [];
        $list = $function->getParameters();
        $special = \false;
        foreach ($list as $param) {
            $variadic = $function->isVariadic() && $param === \end($list);
            $type = $param->getType();
            $promoted = $param instanceof \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PromotedParameter ? $param : null;
            $params[] = ($promoted ? \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Helpers::formatDocComment((string) $promoted->getComment()) : '') . ($attrs = self::printAttributes($param->getAttributes(), $namespace, \true)) . ($promoted ? ($param->getVisibility() ?: 'public') . ' ' : '') . \ltrim($this->printType($type, $param->isNullable(), $namespace) . ' ') . ($param->isReference() ? '&' : '') . ($variadic ? '...' : '') . '$' . $param->getName() . ($param->hasDefaultValue() && !$variadic ? ' = ' . $this->dump($param->getDefaultValue()) : '');
            $special = $special || $promoted || $attrs;
        }
        $line = \implode(', ', $params);
        return \count($params) > 1 && ($special || \strlen($line) > (new \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Dumper())->wrapLength) ? "(\n" . $this->indent(\implode(",\n", $params)) . ($special ? ',' : '') . "\n)" : "({$line})";
    }
    /**
     * @param string|null $type
     */
    public function printType($type, bool $nullable = \false, \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace $namespace = null) : string
    {
        return $type ? ($nullable ? '?' : '') . ($this->resolveTypes && $namespace ? $namespace->unresolveUnionType($type) : $type) : '';
    }
    /**
     * @param Closure|GlobalFunction|Method  $function
     * @param \_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace|null $namespace
     */
    private function printReturnType($function, $namespace) : string
    {
        return ($tmp = $this->printType($function->getReturnType(), $function->isReturnNullable(), $namespace)) ? $this->returnTypeColon . $tmp : '';
    }
    /**
     * @param \_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\PhpNamespace|null $namespace
     */
    private function printAttributes(array $attrs, $namespace, bool $inline = \false) : string
    {
        if (!$attrs) {
            return '';
        }
        $items = [];
        foreach ($attrs as $attr) {
            $args = (new \RectorPrefix20210520\_HumbugBox0b2f2d5c77b8\Nette\PhpGenerator\Dumper())->format('...?', $attr->getArguments());
            $items[] = $this->printType($attr->getName(), \false, $namespace) . ($args ? "({$args})" : '');
        }
        return $inline ? '#[' . \implode(', ', $items) . '] ' : '#[' . \implode("]\n#[", $items) . "]\n";
    }
    private function joinProperties(array $props)
    {
        return $this->linesBetweenProperties ? \implode(\str_repeat("\n", $this->linesBetweenProperties), $props) : \preg_replace('#^(\\w.*\\n)\\n(?=\\w.*;)#m', '$1', \implode("\n", $props));
    }
}
