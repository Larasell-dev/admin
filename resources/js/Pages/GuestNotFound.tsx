import NotFoundState from "../Components/NotFoundState";

/** The 404 page for guests, without the sidebar they can't use. */
export default function GuestNotFound() {
  return (
    <main className="flex min-h-dvh items-center justify-center bg-white px-6 py-12 dark:bg-mauve-950">
      <NotFoundState />
    </main>
  );
}
