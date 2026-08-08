# DataTable Package

A server-rendered data table for NeoPHP — real SQL pagination, sorting,
and search from a single entity class. No AJAX, no JavaScript required
to function: every interaction is a plain link or GET form that reloads
the page with new query parameters. Reuses NeoPHP core's own `Paginator`
and `paginator_links()` — no duplicate pagination logic.

---

## Structure

```
datatable-package/
├── composer.json
├── README.md
└── src/
    ├── NeoDataTablePackage.php
    ├── Service/
    │   ├── DataTableFactory.php
    │   └── DataTableResult.php
    ├── Assets/
    │   └── css/datatable.css
    └── Templates/
        └── components/
            └── DataTable.macro.html.twig
```

---

## How it works

You give `DataTableFactory` an entity class, the columns to display, and
the current request's query parameters. It runs a real, paginated,
sorted SQL query through NeoPHP's `EntityManager`/`EntityRepository` —
nothing is loaded into memory beyond the current page's rows.

```php
public function index(Request $request, DataTableFactory $dataTableFactory): Response
{
    $table = $dataTableFactory->createFromEntity(
        AdminUser::class,
        $request->query(),
        columns: [
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'firstName', 'label' => 'First Name', 'sortable' => true],
            ['key' => 'lastName', 'label' => 'Last Name', 'sortable' => true],
        ],
        searchableFields: ['email', 'firstName', 'lastName'],
    );

    return $this->render('pages/admin/users.html.twig', ['table' => $table]);
}
```

```twig
{% import '@DataTable/components/DataTable.macro.html.twig' as DataTable %}

{{ DataTable.render(table) }}
```

That's the entire integration — one factory call, one macro render.

---

## Installation

```bash
php bin/neo package:require neophp/datatable-package --project=MyProject
```

Register it in the project's `Config/app.config.php`:

```php
return [
    // ...
    'packages' => [
        \Vendor\NeoPHP\DataTablePackage\NeoDataTablePackage::class,
    ],
];
```

No configuration file, no migration.

---

## `DataTableFactory::createFromEntity()`

| Parameter | Type | Purpose |
|---|---|---|
| `entityClass` | `class-string` | The entity to query |
| `queryParams` | `array<string, string>` | Typically `$request->query()` — reads `search`, `sort`, `direction`, `page` |
| `columns` | `list<array{key, label, sortable?}>` | Which entity fields to display, in order |
| `searchableFields` | `list<string>` | Which fields the search box matches against |
| `perPage` | `int` | Defaults to 20 |

Only fields listed in `columns` are ever read from the entity — the rest
of the entity's data is never touched or exposed.

---

## No AJAX, by design

Sorting a column, searching, and changing page are all plain links or a
GET form — clicking a column header reloads the page with
`?sort=email&direction=desc` in the URL. This keeps the package simple
and avoids exposing a generic query endpoint that would need careful
security review (allowed entities, allowed fields, injection risks).

If you need a fully dynamic, no-reload experience, this package is not
built for that — you would need to build your own AJAX layer around
`DataTableFactory`, which still only ever runs the query you configured
(no arbitrary entity/field can be requested from the client).

---

## Theming

Every visual value is a CSS custom property scoped to `.dt-wrapper` —
override them on an ancestor element to match your project:

```css
.dt-wrapper {
    --dt-accent: #6366f1;
    --dt-bg: #161923;
    --dt-bg-alt: #1b2030;
    --dt-border: #2d3342;
    --dt-text: #e5e7eb;
    --dt-text-muted: #9ca3af;
}
```

You can also skip `datatable.css` entirely and style the `.dt-*` class
names yourself for full control.

---

## Known limitations

- **Search implementation depends on `EntityRepository`'s own
  capabilities.** This package does not build raw SQL itself — it relies
  entirely on `EntityRepository::findBy()`/`count()`. If your version of
  `EntityRepository` does not support a `LIKE`-style multi-field search
  through its criteria array, the `search` feature may not filter
  results as expected. Sorting and pagination do not have this
  limitation, as they rely on `orderBy`/`limit`/`offset`, which are
  standard `findBy()` parameters.
- **No column formatting.** A column's raw entity field value is
  displayed as-is (`{{ row[column.key] }}`) — dates, booleans, and
  relations are not automatically formatted. Format them yourself before
  passing rows, or extend the macro in your own project.
- **No relation/joined columns.** Only scalar fields directly on the
  queried entity can be displayed — no dot-notation for related entity
  fields (e.g. `role.name`).

---

## License

MIT