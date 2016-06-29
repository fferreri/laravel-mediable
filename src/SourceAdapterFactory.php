<?php

namespace Frasmage\Mediable;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Frasmage\Mediable\Exceptions\MediaUploadException;
use Frasmage\Mediable\UploadSourceAdapters\SourceAdapterInterface;

class SourceAdapterFactory{

    /**
     * Map of which adapters to use for a given source class
     * @var array
     */
    private $class_adapters = [];

    /**
     * Map of which adapters to use for a given string pattern
     * @var array
     */
    private $pattern_adapters = [];

    /**
     * Create a Source Adapter for the provided source
     * @param  Object|string $source
     * @return SourceAdapterInterface
     * @throws MediaUploadException If the provided source does not match any of the mapped classes or patterns
     */
    public function create($source)
    {
        if($source instanceof SourceAdapterInterface){
            return $source;
        }
        else if(is_object($source)){
            $class = $this->adaptClass($source);
            return new $class($source);
        }
        else if(is_string($source)){
            $class = $this->adaptString($source);
            return new $class($source);
        }

        throw MediaUploadException::unrecognizedSource($source);
    }

    /**
     * Specify the FQCN of a SourceAdapter class to use when the source inherits from a given class
     * @param string $adapter_class
     * @param string $source_class
     * @return void
     * @throws MediaUploadException If class is not valid
     */
    public function setAdapterForClass($adapter_class, $source_class)
    {
        $this->validateAdapterClass($adapter_class);
        $this->class_adapters[$source_class] = $adapter_class;
    }

    /**
     * Specify the FQCN of a SourceAdapter class to use when the source is a string matching the given pattern
     * @param string $adapter_class
     * @param string $source_class
     * @return void
     * @throws MediaUploadException If class is not valid
     */
    public function setAdapterForPattern($adapter_class, $source_pattern)
    {
        $this->validateAdapterClass($adapter_class);
        $this->pattern_adapters[$source_pattern] = $adapter_class;
    }

    /**
     * Choose an adapter class for the class of the provided object
     * @param  object $source
     * @return SourceAdapterInterface|null
     */
    private function adaptClass($source)
    {
        $tree = class_parents($source);
        array_unshift($tree, get_class($source))
        foreach ($this->class_adapters as $class => $adapter) {
            if(in_array($class, $tree)){
                return $adapter;
            }
        }
    }

    /**
     * Choose an adapter class for the provided string
     * @param  string $source
     * @return SourceAdapterInterface|null
     */
    private function adaptString($source)
    {
        foreach($this->pattern_adapters as $pattern => $adapter){
            $pattern = '/'.str_replace('/', '\\/', $pattern).'/i';
            if(preg_match($pattern, $source)){
                return $adapter;
            }
        }
    }

    /**
     * Verify that the provided class implements the SourceAdapterInterface
     * @param  string $class
     * @throws MediaUploadException If class is not valid
     * @return void
     */
    private function validateAdapterClass($class){
        if(!is_subclass_of($class, SourceAdapterInterface::class)){
            throw MediaUploadException::cannotSetAdaptor($class);
        }
    }

}