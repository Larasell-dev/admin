import ErrorState, { type ErrorStatus } from "../Components/ErrorState";

/** The error page for guests, without the sidebar they can't use. */
export default function GuestErrorPage({ status }: { status: ErrorStatus }) {
  return (
    <main className="flex min-h-dvh items-center justify-center bg-base-canvas px-6 py-12">
      <ErrorState status={status} />
    </main>
  );
}
