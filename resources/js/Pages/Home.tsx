import { Container, Stack } from "@larasell-dev/ui";
import SidebarLayout from "../Layouts/SidebarLayout";

export default function Home({ admin }: { admin: { name: string; email: string } }) {
  return (
    <Container>
      <div className="py-10">
        <Stack spacing={2}>
          <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Dashboard</h1>
          <p className="type-body-md text-mauve-600 dark:text-mauve-400">
            {admin.name} ({admin.email})
          </p>
        </Stack>
      </div>
    </Container>
  );
}

Home.layout = [SidebarLayout];
