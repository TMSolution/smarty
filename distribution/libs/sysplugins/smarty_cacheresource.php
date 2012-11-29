<?php

/**
 * Smarty Internal Plugin
 *
 * @package Smarty
 * @subpackage Cacher
 */

/**
 * Cache Handler API
 *
 * @package Smarty
 * @subpackage Cacher
 * @author Rodney Rehm
 */
abstract class Smarty_CacheResource {

    /**
     * cache for Smarty_CacheResource instances
     * @var array
     */
    public static $resources = array();

    /**
     * resource types provided by the core
     * @var array
     */
    protected static $sysplugins = array(
        'file' => true,
    );

    /**
     * populate Cached Object with meta data from Resource
     *
     * @param Smarty_Template_Cached $cached cached object
     * @param Smarty_Internal_Template $_template template object
     * @return void
     */
    public abstract function populate(Smarty_Template_Cached $cached, Smarty_Internal_Template $_template);

    /**
     * populate Cached Object with timestamp and exists from Resource
     *
     * @param Smarty_Template_Cached $source cached object
     * @return void
     */
    public abstract function populateTimestamp(Smarty_Template_Cached $cached);

    /**
     * Read the cached template and process header
     *
     * @param Smarty_Internal_Template $_template template object
     * @param Smarty_Template_Cached $cached cached object
     * @return booelan true or false if the cached content does not exist
     */
    public abstract function process(Smarty_Internal_Template $_template, Smarty_Template_Cached $cached=null);

    /**
     * Write the rendered template output to cache
     *
     * @param Smarty_Internal_Template $_template template object
     * @param string $content content to cache
     * @return boolean success
     */
    public abstract function writeCachedContent(Smarty_Internal_Template $_template, $content);

    /**
     * Return cached content
     *
     * @param Smarty_Internal_Template $_template template object
     * @param string $content content of cache
     */
    public function getCachedContent(Smarty_Internal_Template $_template) {
        if ($_template->cached->handler->process($_template)) {
            return $_template->cached->smarty_content->get_template_content($_template);
        }
        return null;
    }

    /**
     * Empty cache
     *
     * @param Smarty $smarty Smarty object
     * @param integer $exp_time expiration time (number of seconds, not timestamp)
     * @return integer number of cache files deleted
     */
    public abstract function clearAll(Smarty $smarty, $exp_time=null);

    /**
     * Empty cache for a specific template
     *
     * @param Smarty $smarty Smarty object
     * @param string $resource_name template name
     * @param string $cache_id cache id
     * @param string $compile_id compile id
     * @param integer $exp_time expiration time (number of seconds, not timestamp)
     * @return integer number of cache files deleted
     */
    public abstract function clear(Smarty $smarty, $resource_name, $cache_id, $compile_id, $exp_time);

    public function locked(Smarty $smarty, Smarty_Template_Cached $cached) {
        // theoretically locking_timeout should be checked against time_limit (max_execution_time)
        $start = microtime(true);
        $hadLock = null;
        while ($this->hasLock($smarty, $cached)) {
            $hadLock = true;
            if (microtime(true) - $start > $smarty->locking_timeout) {
                // abort waiting for lock release
                return false;
            }
            sleep(1);
        }
        return $hadLock;
    }

    public function hasLock(Smarty $smarty, Smarty_Template_Cached $cached) {
        // check if lock exists
        return false;
    }

    public function acquireLock(Smarty $smarty, Smarty_Template_Cached $cached) {
        // create lock
        return true;
    }

    public function releaseLock(Smarty $smarty, Smarty_Template_Cached $cached) {
        // release lock
        return true;
    }

    /**
     * Load Cache Resource Handler
     *
     * @param Smarty $smarty Smarty object
     * @param string $type name of the cache resource
     * @return Smarty_CacheResource Cache Resource Handler
     */
    public static function load(Smarty $smarty, $type = null) {
        if (!isset($type)) {
            $type = $smarty->caching_type;
        }

        // try smarty's cache
        if (isset($smarty->_cacheresource_handlers[$type])) {
            return $smarty->_cacheresource_handlers[$type];
        }

        // try registered resource
        if (isset($smarty->registered_cache_resources[$type])) {
            // do not cache these instances as they may vary from instance to instance
            return $smarty->_cacheresource_handlers[$type] = $smarty->registered_cache_resources[$type];
        }
        // try sysplugins dir
        if (isset(self::$sysplugins[$type])) {
            if (!isset(self::$resources[$type])) {
                $cache_resource_class = 'Smarty_Internal_CacheResource_' . ucfirst($type);
                self::$resources[$type] = new $cache_resource_class();
            }
            return $smarty->_cacheresource_handlers[$type] = self::$resources[$type];
        }
        // try plugins dir
        $cache_resource_class = 'Smarty_CacheResource_' . ucfirst($type);
        if ($smarty->loadPlugin($cache_resource_class)) {
            if (!isset(self::$resources[$type])) {
                self::$resources[$type] = new $cache_resource_class();
            }
            return $smarty->_cacheresource_handlers[$type] = self::$resources[$type];
        }
        // give up
        throw new SmartyException("Unable to load cache resource '{$type}'");
    }

    /**
     * Invalid Loaded Cache Files
     *
     * @param Smarty $smarty Smarty object
     */
    public static function invalidLoadedCache(Smarty $smarty) {
        foreach (Smarty::$template_objects as $tpl) {
            if (isset($tpl->cached)) {
                $tpl->cached->valid = false;
                $tpl->cached->processed = false;
            }
        }
    }

}

/**
 * Smarty Resource Data Object
 *
 * Cache Data Container for Template Files
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Rodney Rehm
 */
class Smarty_Template_Cached {

    /**
     * Source Filepath
     * @var string
     */
    public $filepath = false;

    /**
     * Source Content
     * @var string
     */
    public $content = null;

    /**
     * instance of smarty content from cached file
     * @var smarty_content
     * @internal
     */
    public $smarty_content = null;

    /**
     * Source Timestamp
     * @var integer
     */
    public $timestamp = false;

    /**
     * Source Existance
     * @var boolean
     */
    public $exists = false;

    /**
     * Cache Is Valid
     * @var boolean
     */
    public $valid = false;

    /**
     * Cache was processed
     * @var boolean
     */
    public $processed = false;

    /**
     * CacheResource Handler
     * @var Smarty_CacheResource
     */
    public $handler = null;

    /**
     * Template Compile Id (Smarty_Internal_Template::$compile_id)
     * @var string
     */
    public $compile_id = null;

    /**
     * Template Cache Id (Smarty_Internal_Template::$cache_id)
     * @var string
     */
    public $cache_id = null;

    /**
     * Id for cache locking
     * @var string
     */
    public $lock_id = null;

    /**
     * flag that cache is locked by this instance
     * @var bool
     */
    public $is_locked = false;

    /**
     * Source Object
     * @var Smarty_Template_Source
     */
    public $source = null;

    /**
     * Code Object
     * @var Smarty_Internal_Output
     */
    public $code = null;

    /**
     * required plugins
     * @var array
     * @internal
     */
    public $required_plugins = array();

    /**
     * template function properties
     *
     * @var array
     */
    public $template_functions = array();

    /**
     * template function properties
     *
     * @var array
     */
    public $template_functions_code = array();

    /**
     * file dependencies
     *
     * @var array
     */
    public $file_dependency = array();

    /**
     * create Cached Object container
     *
     * @param Smarty_Internal_Template $_template template object
     */
    public function __construct(Smarty_Internal_Template $_template) {
        $this->compile_id = $_template->compile_id;
        $this->cache_id = $_template->cache_id;
        $this->source = $_template->source;
        $_template->cached = $this;

        //
        // load resource handler
        //
        $this->handler = $handler = Smarty_CacheResource::load($_template); // Note: prone to circular references
        //
        //    check if cache is valid
        //
        if (!($_template->caching == Smarty::CACHING_LIFETIME_CURRENT || $_template->caching == Smarty::CACHING_LIFETIME_SAVED) || $_template->source->recompiled) {
            $handler->populate($this, $_template);
            return;
        }
        while (true) {
            while (true) {
                $handler->populate($this, $_template);
                if ($this->timestamp === false || $_template->force_compile || $_template->force_cache) {
                    $this->valid = false;
                } else {
                    $this->valid = true;
                }
                if ($this->valid && $_template->caching == Smarty::CACHING_LIFETIME_CURRENT && $_template->cache_lifetime >= 0 && time() > ($this->timestamp + $_template->cache_lifetime)) {
                    // lifetime expired
                    $this->valid = false;
                }
                if ($this->valid || !$_template->cache_locking) {
                    break;
                }
                if (!$this->handler->locked($_template, $this)) {
                    $this->handler->acquireLock($_template, $this);
                    break 2;
                }
            }
            if ($this->valid) {
                if (!$_template->cache_locking || $this->handler->locked($_template, $this) === null) {
                    // load cache file for the following checks
                    if ($_template->debugging) {
                        Smarty_Internal_Debug::start_cache($_template);
                    }
                    if ($handler->process($_template, $this) === false) {
                        $this->valid = false;
                    } else {
                        $this->processed = true;
                    }
                    if ($_template->debugging) {
                        Smarty_Internal_Debug::end_cache($_template);
                    }
                } else {
                    continue;
                }
            } else {
                return;
            }
            if ($this->valid && $_template->caching === Smarty::CACHING_LIFETIME_SAVED && $_template->cached->smarty_content->cache_lifetime >= 0 && (time() > ($_template->cached->timestamp + $_template->cached->smarty_content->cache_lifetime))) {
                $this->valid = false;
            }
            if (!$this->valid && $_template->cache_locking) {
                $this->handler->acquireLock($_template, $this);
                return;
            } else {
                return;
            }
        }
    }

    /**
     * get rendered template output from cached template
     *
     * @param Smarty_Internal_Template|Smarty   $obj calling object
     * @param Smarty_Internal_Template          $_template template object
     * @param bool $no_output_filter flsg that output filter shall be ignored
     */
    public function getRenderedTemplate($obj, $_template, $no_output_filter) {
        if (!$this->valid) {
            $_output = $_template->compiled->getRenderedTemplate($obj, $_template);
            // write to cache when nessecary
            if (!$_template->source->recompiled) {
                if ($obj->debugging) {
                    Smarty_Internal_Debug::start_cache($_template);
                }
                $this->code = new Smarty_Internal_Code(3);
                $_template->has_nocache_code = false;
                // get text between non-cached items
                $cache_split = preg_split("!/\*%%SmartyNocache%%\*\/(.+?)/\*/%%SmartyNocache%%\*/!s", $_output);
                // get non-cached items
                preg_match_all("!/\*%%SmartyNocache%%\*\/(.+?)/\*/%%SmartyNocache%%\*/!s", $_output, $cache_parts);
                unset($_output);
                // loop over items, stitch back together
                foreach ($cache_split as $curr_idx => $curr_split) {
                    if (!empty($curr_split)) {
                        $this->code->php("echo ")->string($curr_split)->raw(";\n");
                    }
                    if (isset($cache_parts[0][$curr_idx])) {
                        $_template->has_nocache_code = true;
                        // format and add nocache PHP code
                        $this->code->formatPHP(preg_replace("!/\*/?%%SmartyNocache%%\*/!", '', $cache_parts[0][$curr_idx]));
                    }
                }
                if (!$no_output_filter && !$_template->has_nocache_code && (isset($obj->autoload_filters['output']) || isset($obj->registered_filters['output']))) {
                    $this->code->buffer = Smarty_Internal_Filter_Handler::runFilter('output', $this->code->buffer, $_template);
                }
                // write cache file content
                if (!$_template->source->recompiled && ($_template->caching == Smarty::CACHING_LIFETIME_CURRENT || $_template->caching == Smarty::CACHING_LIFETIME_SAVED)) {
                    $this->code->buffer = $this->code->createTemplateCodeFrame($_template, null, true);
                    try {
                        $level = ob_get_level();
                        eval('?>' . $this->code->buffer);
                        $this->write($_template, $this->code->buffer);
                        $this->code = null;
                        $this->valid = true;
                        $this->processed = true;
                        $output = $this->smarty_content->get_template_content($_template);
                    } catch (Exception $e) {
                        while (ob_get_level() > $level) {
                            ob_end_clean();
                        }
                        throw $e;
                    }
                }
                if ($obj->debugging) {
                    Smarty_Internal_Debug::end_cache($_template);
                }
            }
        } else {
            if ($obj->debugging) {
                Smarty_Internal_Debug::start_cache($_template);
            }
            $_template->is_nocache = true;
            try {
                $level = ob_get_level();
                array_unshift($_template->_capture_stack, array());
                //
                // render cached template
                //
                $output = $this->smarty_content->get_template_content($_template);
                // any unclosed {capture} tags ?
                if (isset($_template->_capture_stack[0][0])) {
                    $_template->_capture_error();
                }
                array_shift($_template->_capture_stack);
            } catch (Exception $e) {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
                throw $e;
            }
            $_template->is_nocache = false;
            if ($obj->debugging) {
                Smarty_Internal_Debug::end_cache($_template);
            }
        }
        return $output;
    }

    /**
     * Write this cache object to handler
     *
     * @param Smarty_Internal_Template $_template template object
     * @param string $content content to cache
     * @return boolean success
     */
    public function write(Smarty_Internal_Template $_template, $content) {
        if (!$_template->source->recompiled) {
            if ($this->handler->writeCachedContent($_template, $content)) {
                $this->timestamp = time();
                $this->exists = true;
                $this->valid = true;
                if ($_template->cache_locking) {
                    $this->handler->releaseLock($_template, $this);
                }
                return true;
            }
        }
        return false;
    }

}
