export type NavigationItem = {
    label: string;
    url: string;
};

export type AdminSummary = { name: string; email: string; avatarUrl: string | null };

export type SharedProps = {
    navigation: NavigationItem[];
    admin: AdminSummary | null;
    profileUrl: string;
    logoutUrl: string;
};

export type Page<TProps extends object = Record<string, never>> = SharedProps & TProps;

export type ProfilePageProps = {
    admin: AdminSummary;
    updateUrl: string;
    avatarUploadUrl: string;
};
