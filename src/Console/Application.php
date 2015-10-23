<?php

namespace SensioLabs\DeprecationDetector\Console;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Pimple\Container;
use SensioLabs\DeprecationDetector\AncestorResolver;
use SensioLabs\DeprecationDetector\Console\Command\CheckCommand;
use SensioLabs\DeprecationDetector\Violation\ViolationDetector;
use SensioLabs\DeprecationDetector\TypeGuessing\ConstructorResolver\Visitor\ConstructorResolverVisitor;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\ReattachStateToProperty;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\ReattachStateToVariable;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\PropertyAssignResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\ArgumentResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\SymfonyResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Resolver\VariableAssignResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\SymbolTable;
use SensioLabs\DeprecationDetector\TypeGuessing\ConstructorResolver\ConstructorResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\ComposedResolver;
use SensioLabs\DeprecationDetector\TypeGuessing\SymbolTable\Visitor\SymbolTableVariableResolverVisitor;
use SensioLabs\DeprecationDetector\TypeGuessing\Symfony\ContainerReader;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\ClassViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\ComposedViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\InterfaceViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\MethodDefinitionViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\MethodViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\SuperTypeViolationChecker;
use SensioLabs\DeprecationDetector\Violation\ViolationChecker\TypeHintViolationChecker;
use SensioLabs\DeprecationDetector\Visitor\Deprecation\FindDeprecatedTagsVisitor;
use SensioLabs\DeprecationDetector\EventListener\CommandListener;
use SensioLabs\DeprecationDetector\Finder\ParsedPhpFileFinder;
use SensioLabs\DeprecationDetector\Parser\DeprecationParser;
use SensioLabs\DeprecationDetector\Parser\UsageParser;
use SensioLabs\DeprecationDetector\RuleSet\Cache;
use SensioLabs\DeprecationDetector\RuleSet\Loader\ComposerLoader;
use SensioLabs\DeprecationDetector\RuleSet\Loader\DirectoryLoader;
use SensioLabs\DeprecationDetector\RuleSet\Loader\FileLoader;
use SensioLabs\DeprecationDetector\RuleSet\DirectoryTraverser;
use SensioLabs\DeprecationDetector\Violation\Renderer;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindMethodDefinitions;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindArguments;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindClasses;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindInterfaces;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindMethodCalls;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindStaticMethodCalls;
use SensioLabs\DeprecationDetector\Visitor\Usage\FindSuperTypes;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;

class Application extends BaseApplication
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    public function __construct()
    {
        parent::__construct('SensioLabs Deprecation Detector', 'dev');
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new CommandListener());

        $checkCommand = new CheckCommand('check');
        $this->setDispatcher($this->dispatcher);
        $this->add($checkCommand);
        $this->setDefaultCommand('check');
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
