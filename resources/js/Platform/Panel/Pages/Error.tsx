import ErrorState, { type ErrorStatus } from "../Components/ErrorState";
import SidebarLayout from "../Layouts/SidebarLayout";

export default function ErrorPage({ status }: { status: ErrorStatus }) {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <ErrorState status={status} />
    </div>
  );
}

ErrorPage.layout = [SidebarLayout];
