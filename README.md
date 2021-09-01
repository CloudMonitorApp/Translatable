Translate Eloquent attributes into multiple languages and use `getLocale()` to seamlessly query the right translation.

### Prepare Eloquent models

Similar to other special attributes in Eloquent, such as `$fillables`, translatable attributes must be defined as an array. It is as simple as giving the name of the database column:

```
protected $translatable = [
    'name',
];
```

Translatable will now only be observing these attributes and skip the rest.

### Using translations

As Translatable uses Laravels `app()->getLocale()` it means it will figure out which language to use when you query name.

For instance, your locale is currently `da` (Danish), so you want to update a book title. Simply do it as there was no translation implementation:

```
$book = Book::find($id);
$book->name = 'New name for Danish version';
$book->save();
```

Or as an update method:

```
Book::find($id)->update(['name' => 'New name for Danish version']);
```

Similar when you want to get the name in the current locale you simply query it:

```
return Book::find($id)->name;
```

#### Other locales

Sometimes you want to update all translations or in a specific language or simply in a different than you are using. It could be a Danish moderator who wants to update the English title, titles for several languages, or something different.

```
Book::find($id)->setTranslation('name', 'en', 'Name in English');
```

Similarly, a specific language can be queried:

```
Book::find($id)->getTranslation('name', 'en');
```

### Migrations

Behind the scenes Translatable uses `JSON` columns in the database to store multiple versions in the same column:

```
$table->json('name');
```
