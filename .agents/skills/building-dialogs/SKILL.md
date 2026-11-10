---
name: building-dialogs
description: How to build dialogs in the admin panel, above all confirmations of destructive actions (delete, revoke, remove). Use when adding or changing a dialog, an alert dialog, a confirmation, or a delete button in resources/js.
---

# Building dialogs

Dialogs come from `@larasell-dev/ui` (`Dialog`, `AlertDialog`). Two panel components build on
`AlertDialog`, in `resources/js/Platform/Panel/Components/`:

- `ConfirmDialog`: every confirmation of a destructive action.
- `PersistentAlertDialog`: an alert dialog whose popup the layout draws, for dialogs that leave the page.

## Confirmations: always `ConfirmDialog`

A confirmation is always destructive, so don't build one from `AlertDialog` yourself.
`ConfirmDialog.Popup` brings the parts every confirmation shares: an Inertia `<Form>` that sends
the request (method `delete` by default), shows its loading state and errors and closes the
dialog once it succeeds; the optional type-to-confirm field; Cancel and a destructive confirm
button. Pass only what differs:

```tsx
<ConfirmDialog.Root>
  <ConfirmDialog.Trigger render={<Button variant="destructive" />}>Delete</ConfirmDialog.Trigger>
  <ConfirmDialog.Popup
    title={fbs("Delete domain", "…")}
    description={fbs([…], "…")}
    action={entryPoint.destroyUrl}
    confirmationText={entryPoint.domain} // optional
  />
</ConfirmDialog.Root>
```

No `useState` for open, typed text, processing or errors, and no `router.delete` with handlers.
The server checks the typed text with the `Confirmation` rule (`Platform/Panel/Rules`) on the
`confirmation` field; any other error it returns, e.g. why a channel can't be deleted, shows in
the dialog too. Table actions defined in PHP (`Action::confirm()`) reach it through
`ConfirmActionDialog`.

### Copy

- **Title:** "Verb thing", no question mark: "Delete domain", "Revoke API token".
- **Description:** what happens once confirmed, from the admin's view: "Customers can no longer
  open the storefront at https://shop.example/de." End with "This can't be undone." when it
  can't. Name the record when it helps, through `fbs.param`.
- **Confirm button:** the plain verb, "Delete" by default; pass `confirmButton` for another verb
  such as "Revoke" or "Remove". Never "Delete redirect" or "Yes".
- **Type to confirm** (`confirmationText`) for deletions that can't be undone: the record's
  name, or the domain or email when it has no name. A fixed phrase such as "Delete export" only
  when there's nothing else to type; it must match the server's `__()` text.
- Give every text an `fbs` description that says it's in the dialog and what it does.

## Deleting a row: one dialog outside the list

Don't render a dialog inside `.map()` (or inside a row, menu or tree node) when confirming
removes that row. The row unmounts with the dialog in it, so the dialog vanishes instead of
playing its exit animation. Render one controlled dialog beside the list, keep the record in
state, and `Freeze` its content while it closes, so the text doesn't go blank:

```tsx
const [deleting, setDeleting] = useState<EntryPoint | null>(null);

{entryPoints.map((entryPoint) => (
  <IconButton onClick={() => setDeleting(entryPoint)}>…</IconButton>
))}

<ConfirmDialog.Root open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
  <Freeze frozen={deleting === null}>
    {deleting && <ConfirmDialog.Popup … action={deleting.destroyUrl} />}
  </Freeze>
</ConfirmDialog.Root>
```

See `SalesChannel/Components/EntryPoints.tsx`, `Category/Components/CategoryTree.tsx` and
`ConfirmActionDialog`. A trigger next to the dialog (`ConfirmDialog.Trigger`) is fine when the
trigger stays on screen, e.g. a page's own Delete button.

## Leaving the page: `PersistentAlertDialog`

When confirming visits another page, e.g. deleting the record the page shows redirects to a
list or the dashboard, the page unmounts with the dialog and it vanishes mid-animation. Use
`PersistentAlertDialog.Root` and `.Trigger` instead: the layout (`SidebarLayout`'s
`PersistentAlertDialogHost`) draws the popup, so it animates out over the next page.
`ConfirmDialog.Popup` works inside either root; the root picks where it's drawn:

```tsx
<PersistentAlertDialog.Root>
  <PersistentAlertDialog.Trigger render={<Button variant="destructive" />}>Delete</PersistentAlertDialog.Trigger>
  <ConfirmDialog.Popup title={…} description={…} action={destroyUrl} confirmationText={name} />
</PersistentAlertDialog.Root>
```

Use it only for dialogs that leave the page (see `SalesChannel/Pages/Edit.tsx`); keep the
default root otherwise. Its popup content renders in the layout, so it can't read React context
the page provides: pass what it needs as props. It works only under `SidebarLayout`.
