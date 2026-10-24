import NotFoundState from "../Components/NotFoundState";
import SidebarLayout from "../Layouts/SidebarLayout";

export default function NotFound() {
  return (
    <div className="flex min-h-[calc(100dvh-3.5rem)] items-center justify-center px-6 py-12 lg:min-h-dvh">
      <NotFoundState />
    </div>
  );
}

NotFound.layout = [SidebarLayout];
