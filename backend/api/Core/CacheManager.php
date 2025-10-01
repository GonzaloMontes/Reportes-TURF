<?php

namespace TurfReports\Core;

/**
 * Gestor de caché para optimizar performance de consultas
 * Soporta file cache y Redis
 */
class CacheManager
{
    private $config;
    private $redis;
    private $cacheDir;

    public function __construct($config = null)
    {
        $this->config = $config ?: require __DIR__ . '/../../config/database.php';
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        if ($this->config['cache']['driver'] === 'redis') {
            $this->initRedis();
        }
    }

    /**
     * Obtiene valor del caché
     */
    public function get($key)
    {
        if (!$this->config['cache']['enabled']) {
            return null;
        }

        if ($this->config['cache']['driver'] === 'redis' && $this->redis) {
            return $this->getFromRedis($key);
        }
        
        return $this->getFromFile($key);
    }

    /**
     * Guarda valor en caché
     */
    public function set($key, $value, $ttl = null)
    {
        if (!$this->config['cache']['enabled']) {
            return false;
        }

        $ttl = $ttl ?: $this->config['cache']['ttl'];
        
        if ($this->config['cache']['driver'] === 'redis' && $this->redis) {
            return $this->setToRedis($key, $value, $ttl);
        }
        
        return $this->setToFile($key, $value, $ttl);
    }

    /**
     * Elimina valor del caché
     */
    public function delete($key)
    {
        if ($this->config['cache']['driver'] === 'redis' && $this->redis) {
            return $this->redis->del($key);
        }
        
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * Limpia todo el caché
     */
    public function flush()
    {
        if ($this->config['cache']['driver'] === 'redis' && $this->redis) {
            return $this->redis->flushAll();
        }
        
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }

    /**
     * Genera clave de caché para consultas
     */
    public function generateQueryKey($sql, $params = [], $connection = 'unified')
    {
        return 'query_' . md5($connection . $sql . serialize($params));
    }

    /**
     * Cache wrapper para consultas de base de datos
     */
    public function remember($key, $callback, $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Inicializa conexión Redis
     */
    private function initRedis()
    {
        if (!extension_loaded('redis')) {
            error_log('Redis extension not loaded, falling back to file cache');
            $this->config['cache']['driver'] = 'file';
            return;
        }
        
        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->config['cache']['redis_host'],
                $this->config['cache']['redis_port']
            );
        } catch (\Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            $this->redis = null;
            $this->config['cache']['driver'] = 'file';
        }
    }

    /**
     * Obtiene valor de Redis
     */
    private function getFromRedis($key)
    {
        try {
            $value = $this->redis->get($key);
            return $value ? unserialize($value) : null;
        } catch (\Exception $e) {
            error_log('Redis get error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Guarda valor en Redis
     */
    private function setToRedis($key, $value, $ttl)
    {
        try {
            return $this->redis->setex($key, $ttl, serialize($value));
        } catch (\Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene valor de archivo
     */
    private function getFromFile($key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cache = unserialize($data);
        
        if ($cache['expires'] < time()) {
            unlink($filename);
            return null;
        }
        
        return $cache['value'];
    }

    /**
     * Guarda valor en archivo
     */
    private function setToFile($key, $value, $ttl)
    {
        $filename = $this->getCacheFilename($key);
        $cache = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($filename, serialize($cache), LOCK_EX) !== false;
    }

    /**
     * Genera nombre de archivo para caché
     */
    private function getCacheFilename($key)
    {
        return $this->cacheDir . md5($key) . '.cache';
    }
}
