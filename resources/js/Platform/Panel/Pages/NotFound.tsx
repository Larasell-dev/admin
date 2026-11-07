import NotFoundState from "../Components/NotFoundState";
import SidebarLayout from "../Layouts/SidebarLayout";

export default function NotFound() {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <NotFoundState />
    </div>
  );
}

NotFound.layout = [SidebarLayout];
