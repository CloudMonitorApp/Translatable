<?php

namespace CloudMonitor\Translatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait allowing certain properties (columns) in Eloquent to be
 * translatable into different locales.
 * 
 * Default behavior is to return in the currently active locale.
 * 
 * Translatable properties are set in the Eloquent model with $translatable = [],
 * such as protected $translatable = ['name'];
 * 
 * Data stored as JSON (casted automatically), such as for name:
 * {"en": "Value in English", "da": "Value in Danish"}
 * 
 * $model->name will return either the value from en or da based on app()->getLocale().
 * Likewise, $model->name = 'New value' will set the value on the language based on app()->getLocale()
 * 
 * $model->getTranslation('name', 'da') will always return the Danish.
 * Likewise $model->setTranslation('name', 'da', 'New value in Danish')
 */
trait Translatable
{
    /**
     * Override getCasts() to allow trait to set casts.
     *
     * @return array
     */
    public function getCasts()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'get'. class_basename($trait) .'Casts';

            if (method_exists($class, $method)) {
                $this->casts = array_merge(
                    $this->casts,
                    $this->{$method}()
                );
            }
        }

        return parent::getCasts();
    }

    /**
     * Get casts for the current trait.
     * 
     * @return array
     */
    public function getTranslatableCasts()
    {
        $casts = [];

        collect($this->translatable)->each(function ($item) use(&$casts) {
            $casts[$item] = 'json';
        });

        return $casts;
    }

    /**
     * Get translation if matches value in protected $translatable = [].
     * Otherwise calling parent __get($key)
     * 
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, $this->translatable)) {
            return $this->getTranslation($key);
        }

        return parent::__get($key);
    }

    /**
     * Set property value in current language if match found in protected $translatable = [].
     * Otherwise calling parent __set($key, $value)
     * 
     * @return void
     */
    public function __set($key, $value)
    {
        if (in_array($key, $this->translatable)) {
            if (is_array($value)) {
                foreach($value as $locale => $val) {
                    $this->setTranslation($key, $locale, $val);
                }
            } else {
                $this->setTranslation($key, app()->getLocale(), $value);
            }
        }

        parent::__set($key, $value);
    }

    /**
     * Order by translatable columns.
     * 
     * @param Builder $query
     * @param string $column
     * @param string $direction
     * @param string $locale
     * @return void
     */
    public function scopeOrderByTranslation(Builder $query, string $column, string $direction = 'asc', string $locale = null): void
    {
        $locale = $locale ? $locale : app()->getLocale();

        $columns = $query->getQuery()->columns ?? ['*'];
        $query->getQuery()->columns = [];

        foreach($columns as $col) {
            $query->addSelect($col);
        }

        $query->addSelect(DB::raw('JSON_EXTRACT('. $column .', "$.'. $locale .'") AS `TRANS_SORT_'. strtoupper($column) .'`'))
            ->orderBy('TRANS_SORT_'. strtoupper($column), $direction);
    }

    /**
     * Where translation contains.
     * Case insensitive search for translation.
     * 
     * @param Builder $query
     * @param string $column
     * @param string $search
     * @param string $operator
     * @param string $locale
     * @param string $logic
     * @return void
     */
    public function scopeWhereTranslation(Builder $query, string $column, string $search, string $operator = '=', string $locale = null, string $logic = 'and'): void
    {
        $locale = $locale ? $locale : app()->getLocale();

        $columns = $query->getQuery()->columns ?? ['*'];
        $query->getQuery()->columns = [];

        foreach($columns as $col) {
            $query->addSelect($col);
        }

        $query->addSelect(DB::raw('LOWER(JSON_EXTRACT('. $column .', "$.'. $locale .'")) AS `TRANS_WHERE_'. strtoupper($column) .'`'));

        $logic === 'and'
            ? $query->having('TRANS_WHERE_'. strtoupper($column), $operator, strtolower($search))
            : $query->orHaving('TRANS_WHERE_'. strtoupper($column), $operator, strtolower($search));
    }

    /**
     * Where Translation contains.
     * Or equivalent to whereTranslation.
     * 
     * @param Builder $query
     * @param string $column
     * @param string $operator
     * @param string $search
     * @param string $locale
     * @return void
     */
    public function scopeOrWhereTranslation(Builder $query, string $column, string $operator = '=', string $search, string $locale = null): void
    {
        $this->scopeWhereTranslation($query, $column, $search, $operator, $locale, 'or');
    }

    /**
     * Set translation in a given locale.
     * 
     * @param String $key
     * @param String $locale
     * @return void
     */
    public function setTranslation(String $key, String $locale, $value): void
    {
        if (isset($this->attributes[$key])) {
            $attribute = json_decode($this->attributes[$key]);
        }
        else {
            $attribute = [];
        }

        $attributes[$locale] = $value;
        $this->attributes[$key] = json_encode($attribute);
    }

    /**
     * Get all translations.
     * 
     * @param string $key
     * @return \stdClass
     */
    public function getTranslations(string $key): \stdClass
    {
        return json_decode($this->attributes[$key]);
    }

    /**
     * Get translation.
     * 
     * @param string $key
     * @param string $locale
     * @return String
     */
    public function getTranslation(string $key, string $locale = null): String
    {
        $locale = $locale ? $locale : app()->getLocale();

        return json_decode($this->attributes[$key])->{$locale};
    }
}
