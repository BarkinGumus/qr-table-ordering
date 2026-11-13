# Rules the next steps must follow

Found while reviewing the database step. Each is a rule for code that does not
exist yet; tick it off in the step that implements it.

## Never trust an ID sent by the browser (steps 3 to 7)

The database checks that `dining_table_id`, `table_session_id`, `category_id`
and `product_id` exist. It does not check that they belong to the same
restaurant as the row pointing at them.

Rule: every such ID is loaded through its model (`Product::findOrFail($id)`),
which is filtered to the current restaurant, before it is written anywhere.
Each endpoint that accepts an ID gets a test that sends another restaurant's ID
and expects a 404.

## Menu admin deactivates; it does not hard-delete (step 4)

Products are soft-deleted. Deleting a category row would hard-delete its
products. Old order lines survive that (they keep their copied name and price,
and `product_id` becomes NULL), but the link to the product is lost.

Rule: the admin "delete" on a category with products deactivates it
(`is_active = false`) or is refused with a clear message. Products are only
ever soft-deleted.

## Order numbers are handed out under a lock (step 5)

`(restaurant_id, number)` is unique. Two orders arriving at the same moment
must not both read the same "last number".

Rule: take the next number inside the order transaction while holding a row
lock on the restaurant (`lockForUpdate()`), with a test that the unique index
is the last line of defence.

## Platform admin has no restaurant (steps 3, 4, 8)

A platform admin's requests have no current restaurant, so any query on a
restaurant-owned model throws.

Rule: restaurant screens (tables, menu, staff) are hidden from the platform
admin. Platform screens (restaurants list) use `Tenant::withoutScope()`
explicitly.
