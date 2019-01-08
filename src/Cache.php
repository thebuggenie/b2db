<?php

	namespace b2db;

	/**
	 * This class provides a simple common interface and utility
	 * methods for caching key => value pairs. The underlying cache
	 * implementation is opaque, and the caller does not need to worry
	 * too much about the details beyond picking the cache type to
	 * use.
     *
     * At the moment it is suggested to use the file-based cache.
     *
	 * @package b2db\core
	 */
	class Cache implements interfaces\Cache
	{

        /**
         * Dummy cache implementation (never caches anything).
         * @var int
         */
		const TYPE_DUMMY = 0;
        /**
         * In-memory cache (APC).
         * @var int
         */
		const TYPE_APC = 1;
        /**
         * File-based cache implementation.
         * @var int
         */
		const TYPE_FILE = 2;

		/**
         * Specifies if the cache is enabled or not.
		 * @var bool
		 */
		protected $enabled = true;

        /**
         * Type of cache implementation provided by the object. See the TYPE constants.
         * @var int
         */
		protected $type;

        /**
         * Directory under which the file-based cache stores files.
         */
		protected $path;

        /**
         * Initialise an instance.
         *
         * @param int $type Type of cache implementation to use. See document type constants for valid values.
         * @param array $options Options for initialising the cache.
         *     $options = [
         *         'enabled' => bool Specifies if cache is enabled. Default: true.
         *         'path' => string Path to directory where the cache files are stored. Directory must exist and be writable.
         *     ]
         *
         * @throws InvalidConfigurationException if the passed-in arguments hold invalid/unsupported values.
         */
		public function __construct($type, $options = [])
		{
			$this->type = $type;

            foreach ($options as $option => $value)
            {
                if ($option == 'enabled')
                {
                    $this->enabled = $value;
                }
                else if ($option == 'path')
                {
                    if (!file_exists($value))
                    {
                        throw new \Exception("Configured cache path ($value) is not writable. Please check your configuration.");
                    }

                    $this->path = $value;
                }
                else
                {
                    throw new InvalidConfigurationException("Unsupported cache configuration option $option => $value");
                }
            }
		}


		/**
		 * @return string
		 */
		public function getCacheTypeDescription()
		{
			switch ($this->type) {
				case self::TYPE_DUMMY:
					return 'Dummy cache';
				case self::TYPE_APC:
					return 'In-memory cache (apc)';
				case self::TYPE_FILE:
					return 'File cache (' . $this->path . ')';
			}

			return 'Invalid cache type';
		}

		/**
		 * @return int
		 */
		public function getType()
		{
			return $this->type;
		}

		/**
		 * @param string $key The cache key to look up
		 *
		 * @param null $default_value
		 * @return mixed
		 */
		public function get($key, $default_value = null)
		{
			if (!$this->enabled) return $default_value;

			switch ($this->type) {
				case self::TYPE_APC:
					$success = false;
					$var = apc_fetch($key, $success);

					return ($success) ? $var : $default_value;
				case self::TYPE_FILE:
					$filename = $this->path . $key . '.cache';
					if (!file_exists($filename)) return $default_value;

					$value = unserialize(file_get_contents($filename));
					return $value;
				case self::TYPE_DUMMY:
				default:
					return $default_value;
			}
		}

		/**
		 * @param string $key The cache key to look up
		 *
		 * @return bool
		 */
		public function has($key)
		{
			if (!$this->enabled) return false;

			switch ($this->type) {
				case self::TYPE_APC:
					$success = false;
					apc_fetch($key, $success);
					break;
				case self::TYPE_FILE:
					$filename = $this->path . $key . '.cache';
					$success = file_exists($filename);
					break;
				case self::TYPE_DUMMY:
				default:
					$success = false;
			}

			return $success;
		}

		/**
		 * Store an item in the cache
		 *
		 * @param string $key The cache key to store the item under
		 * @param mixed $value The value to store
		 *
		 * @return bool
		 */
		public function set($key, $value)
		{
			if (!$this->enabled) {
				return false;
			}

			switch ($this->type) {
				case self::TYPE_APC:
					apc_store($key, $value);
					break;
				case self::TYPE_FILE:
					$filename = $this->path . $key . '.cache';
					file_put_contents($filename, serialize($value));
					break;
			}


			return true;
		}

		/**
		 * Delete an entry from the cache
		 *
		 * @param string $key The cache key to delete
		 */
		public function delete($key)
		{
			if (!$this->enabled) return;

			switch ($this->type) {
				case self::TYPE_APC:
					apc_delete($key);
					break;
				case self::TYPE_FILE:
					$filename = $this->path . $key . '.cache';
					unlink($filename);
			}
		}

		/**
		 * Set the enabled property
		 *
		 * @param bool $value
		 */
		public function setEnabled($value)
		{
			$this->enabled = $value;
		}

        /**
         * Checks if the cache is currently enabled or not.
         *
         * @returns bool
         */
        public function isEnabled()
        {
            return $this->enabled;
        }

		/**
		 * Temporarily disable the cache
		 */
		public function disable()
		{
			$this->setEnabled(false);
		}

		/**
		 * (Re-)enable the cache
		 */
		public function enable()
		{
			$this->setEnabled(true);
		}

        /**
         * Flush all entries in the cache
         */
        public function flush()
        {
            if (!$this->enabled) return;

            switch ($this->type) {
                case self::TYPE_FILE:
                    $iterator = new \DirectoryIterator($this->path);
                    foreach ($iterator as $file_info)
                    {
                        if (!$file_info->isDir())
                        {
                            unlink($file_info->getPathname());
                        }
                    }
            }
        }

	}
