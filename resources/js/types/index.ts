export type NavigationItem = {
    label: string;
    url: string;
};

export type SharedProps = {
    navigation: NavigationItem[];
    logoutUrl: string;
};

export type Page<TProps extends object = Record<string, never>> = SharedProps & TProps;
