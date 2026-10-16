export type NavigationItem = {
    label: string;
    url: string;
};

export type SharedProps = {
    navigation: NavigationItem[];
    logoutUrl: string;
};

export type Page<TProps extends object = Record<string, never>> = SharedProps & TProps;

export type ProfilePageProps = {
    admin: { name: string; email: string; avatarUrl: string | null };
    updateUrl: string;
    avatarUploadUrl: string;
};
