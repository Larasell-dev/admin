declare namespace Larasell.Admin.ApiToken.Data {
  export type ApiTokensPageData = {
    createUrl: string;
    apiTokens: import("./index").TableResource;
  };
  export type CreateApiTokenPageData = {
    storeUrl: string;
    indexUrl: string;
    expiryOptions: Array<{ value: string; label: string }>;
    defaultExpiresIn: string;
  };
  export type CreatedApiTokenData = {
    name: string;
    plainTextToken: string;
  };
}
declare namespace Larasell.Admin.Category.Data {
  export type CategoriesPageData = {
    categories: Array<Larasell.Admin.Category.Data.CategoryData>;
    createUrl: string;
  };
  export type CategoryData = {
    id: number;
    name: string;
    visible: boolean;
    url: string;
    hideUrl: string;
    unhideUrl: string;
    deleteUrl: string;
    createChildUrl: string;
    moveUrl: string;
    children: Array<Larasell.Admin.Category.Data.CategoryData>;
  };
  export type CategoryDetailData = {
    id: number;
    name: string | null;
    fallbackName: string;
    visible: boolean;
  };
  export type CategoryOptionData = {
    id: number;
    name: string;
    visible: boolean;
    url: string | null;
    children: Array<Larasell.Admin.Category.Data.CategoryOptionData>;
  };
  export type CategoryPageData = {
    categories: Array<Larasell.Admin.Category.Data.CategoryData>;
    createUrl: string;
    category: Larasell.Admin.Category.Data.CategoryDetailData;
    updateUrl: string;
    products: import("./index").TableResource;
    availableProductsUrl: string;
    addProductUrl: string;
    createProductUrl: string | null;
    sections: Array<Larasell.Admin.Platform.Sections.Data.SectionData>;
  };
  export type CreateCategoryPageData = {
    categories: Array<Larasell.Admin.Category.Data.CategoryData>;
    createUrl: string;
    storeUrl: string;
    indexUrl: string;
    parent: Larasell.Admin.Category.Data.CategoryDetailData | null;
    createProductUrl: string | null;
    sections: Array<Larasell.Admin.Platform.Sections.Data.SectionData>;
  };
  export type MissingCategoryPageData = {
    categories: Array<Larasell.Admin.Category.Data.CategoryData>;
    createUrl: string;
    category: null;
  };
}
declare namespace Larasell.Admin.Category.Enums {
  export type CategorySection = "general" | "products";
}
declare namespace Larasell.Admin.Customer.Data {
  export type CreateCustomerPageData = {
    storeUrl: string;
    indexUrl: string;
  };
  export type CustomerDetailData = {
    name: string;
    firstName: string;
    lastName: string;
  };
  export type EditCustomerPageData = {
    customer: Larasell.Admin.Customer.Data.CustomerDetailData;
    updateUrl: string;
    indexUrl: string;
    orders: import("./index").TableResource;
  };
}
declare namespace Larasell.Admin.Order.Data {
  export type AddressData = {
    name: string;
    company: string | null;
    street: Array<string>;
    postcode: string;
    city: string;
    state: string | null;
    country: string;
  };
  export type AmountData = {
    label: string;
    amount: string;
  };
  export type BadgeData = {
    label: string;
    variant: import("@larasell-dev/ui").BadgeVariant;
  };
  export type OrderDetailData = {
    number: string;
    status: Larasell.Admin.Order.Data.BadgeData;
    placedAt: string;
    items: Array<Larasell.Admin.Order.Data.OrderItemData>;
    subtotal: string;
    discount: string | null;
    shipping: Larasell.Admin.Order.Data.AmountData | null;
    taxes: Array<Larasell.Admin.Order.Data.AmountData>;
    taxIncluded: boolean;
    total: string;
    customerName: string;
    customerEmail: string;
    customerPhone: string | null;
    billingAddress: Larasell.Admin.Order.Data.AddressData | null;
    shippingAddress: Larasell.Admin.Order.Data.AddressData | null;
    payments: Array<Larasell.Admin.Order.Data.PaymentData>;
  };
  export type OrderItemData = {
    name: string;
    variant: string | null;
    quantity: number;
    unitPrice: string;
    total: string;
  };
  export type OrderPageData = {
    order: Larasell.Admin.Order.Data.OrderDetailData;
    indexUrl: string;
    customerUrl: string | null;
  };
  export type PaymentData = {
    method: string;
    status: Larasell.Admin.Order.Data.BadgeData;
    reference: string | null;
    amount: string;
  };
}
declare namespace Larasell.Admin.Platform.Audit.Export.Data {
  export type AuditLogExportData = {
    id: number;
    status: Larasell.Admin.Platform.Audit.Export.Enums.AuditLogExportStatus;
    records: number | null;
    createdAt: string;
    expiresAt: string;
    downloadUrl: string | null;
    deleteUrl: string;
  };
}
declare namespace Larasell.Admin.Platform.Audit.Export.Enums {
  export type AuditLogExportStatus = "pending" | "ready" | "failed";
}
declare namespace Larasell.Admin.Platform.Audit.Log.Data {
  export type AuditLogFiltersData = {
    event: string | null;
    actor_type: string | null;
    actor_id: string | null;
    subject_type: string | null;
    subject_id: string | null;
    from: string | null;
    until: string | null;
  };
  export type AuditLogPageData = {
    indexUrl: string;
    auditLog: import("./index").TableResource;
    filters: Larasell.Admin.Platform.Audit.Log.Data.AuditLogFiltersData;
    eventOptions: Array<any>;
    actorTypeOptions: Array<any>;
    subjectTypeOptions: Array<any>;
    canSearch: boolean;
    exportUrl: string | null;
  };
}
declare namespace Larasell.Admin.Platform.Auth.Data {
  export type LoginPageData = {
    loginUrl: string;
    devLoginUrl: string | null;
  };
}
declare namespace Larasell.Admin.Platform.ContentLocale.Data {
  export type ContentLocaleData = {
    current: string;
    options: Array<Larasell.Admin.Platform.ContentLocale.Data.ContentLocaleOptionData>;
    switchUrl: string;
  };
  export type ContentLocaleOptionData = {
    value: string;
    label: string;
  };
}
declare namespace Larasell.Admin.Platform.InterfaceLocale.Data {
  export type InterfaceLocaleData = {
    current: string;
    options: Array<Larasell.Admin.Platform.InterfaceLocale.Data.InterfaceLocaleOptionData>;
    switchUrl: string;
  };
  export type InterfaceLocaleOptionData = {
    value: string;
    label: string;
  };
}
declare namespace Larasell.Admin.Platform.Navigation.Data {
  export type NavigationItemData = {
    id: string;
    label: string;
    url: string;
    icon: string;
    children: Array<Larasell.Admin.Platform.Navigation.Data.NavigationItemData>;
  };
}
declare namespace Larasell.Admin.Platform.Panel.Data {
  export type CommandMenuData = {
    searches: Array<Larasell.Admin.Platform.Search.Data.SearchData>;
  };
  export type HomePageData = {
    createProductUrl: string | null;
    createCategoryUrl: string | null;
    inviteMemberUrl: string;
    helpEmail: string;
  };
}
declare namespace Larasell.Admin.Platform.Search.Data {
  export type SearchData = {
    id: string;
    label: string;
    icon: string;
    placeholder: string;
    url: string;
  };
  export type SearchMatchData = {
    label: string;
    value: string;
  };
  export type SearchResultData = {
    id: number;
    label: string;
    url: string;
    match: Larasell.Admin.Platform.Search.Data.SearchMatchData | null;
  };
}
declare namespace Larasell.Admin.Platform.Sections {
  export type SectionForm = "product" | "category";
}
declare namespace Larasell.Admin.Platform.Sections.Data {
  export type SectionData = {
    form: string;
    key: string;
    props: any;
    before: string | null;
    after: string | null;
  };
}
declare namespace Larasell.Admin.Platform.Settings.Data {
  export type SettingsPageData = {
    id: string;
    label: string;
    description: string | null;
    icon: string;
    url: string;
  };
}
declare namespace Larasell.Admin.Product.Data {
  export type CreateProductPageData = {
    storeUrl: string;
    indexUrl: string;
    categories: Array<Larasell.Admin.Category.Data.CategoryOptionData>;
    createCategoryUrl: string | null;
    sections: Array<Larasell.Admin.Platform.Sections.Data.SectionData>;
  };
  export type DimensionsData = {
    length: string;
    width: string;
    height: string;
    unit: string;
  };
  export type EditProductPageData = {
    product: Larasell.Admin.Product.Data.ProductDetailData;
    updateUrl: string;
    indexUrl: string;
    categories: Array<Larasell.Admin.Category.Data.CategoryOptionData>;
    createCategoryUrl: string | null;
    sections: Array<Larasell.Admin.Platform.Sections.Data.SectionData>;
  };
  export type ProductDetailData = {
    id: number;
    name: string | null;
    fallbackName: string;
    visible: boolean;
    weight: Larasell.Admin.Product.Data.WeightData | null;
    dimensions: Larasell.Admin.Product.Data.DimensionsData | null;
    sku: string | null;
    barcode: string | null;
    stock: number | null;
    allowBackorders: boolean;
    minQuantity: number | null;
    maxQuantity: number | null;
    categoryIds: Array<number>;
  };
  export type WeightData = {
    amount: string;
    unit: string;
  };
}
declare namespace Larasell.Admin.Product.Enums {
  export type ProductSection =
    | "general"
    | "categories"
    | "stock"
    | "weight-and-dimensions"
    | "identifiers";
}
declare namespace Larasell.Admin.SalesChannel.Data {
  export type CreateSalesChannelPageData = {
    storeUrl: string;
    types: Array<string>;
    locales: Array<Larasell.Admin.SalesChannel.Data.SalesChannelOptionData>;
    currencies: Array<Larasell.Admin.SalesChannel.Data.SalesChannelOptionData>;
  };
  export type EditSalesChannelPageData = {
    channel: Larasell.Admin.SalesChannel.Data.SalesChannelData;
    updateUrl: string;
    destroyUrl: string;
    locales: Array<Larasell.Admin.SalesChannel.Data.SalesChannelOptionData>;
    currencies: Array<Larasell.Admin.SalesChannel.Data.SalesChannelOptionData>;
    entryPoints: Array<Larasell.Admin.SalesChannel.Data.EntryPointData> | null;
    addEntryPointUrl: string | null;
  };
  export type EntryPointData = {
    id: number;
    url: string;
    domain: string;
    locale: string;
    defaultCurrency: string | null;
    primary: boolean;
    destroyUrl: string;
  };
  export type SalesChannelData = {
    id: number;
    key: string;
    name: string;
    type: string;
    locales: Array<string>;
    currencies: Array<string>;
    defaultLocale: string;
    defaultCurrency: string;
    active: boolean;
  };
  export type SalesChannelLinkData = {
    id: number;
    name: string;
    url: string;
  };
  export type SalesChannelNavigationData = {
    channels: Array<Larasell.Admin.SalesChannel.Data.SalesChannelLinkData>;
    createUrl: string;
  };
  export type SalesChannelOptionData = {
    value: string;
    label: string;
  };
}
declare namespace Larasell.Admin.Seo.Data {
  export type OldUrlData = {
    id: number;
    path: string;
    removeUrl: string;
    restoreUrl: string;
  };
  export type OldUrlsData = {
    urls: Array<Larasell.Admin.Seo.Data.OldUrlData>;
    page: number;
    pageSize: number;
    total: number;
  };
  export type SeoUrlData = {
    channelId: number;
    channel: string;
    baseUrl: string;
    customPath: string | null;
    automaticPath: string | null;
    history: Larasell.Admin.Seo.Data.OldUrlsData;
  };
  export type SeoUrlsData = {
    urls: Array<Larasell.Admin.Seo.Data.SeoUrlData>;
  };
}
declare namespace Larasell.Admin.Team.Data {
  export type AdminData = {
    name: string;
    email: string;
    avatarUrl: string | null;
  };
  export type CreateMemberPageData = {
    storeUrl: string;
    indexUrl: string;
  };
  export type EditMemberPageData = {
    member: Larasell.Admin.Team.Data.AdminData;
    updateUrl: string;
    indexUrl: string;
  };
  export type ProfilePageData = {
    admin: Larasell.Admin.Team.Data.AdminData;
    updateUrl: string;
    avatarUploadUrl: string;
  };
  export type TeamPageData = {
    createUrl: string;
    members: import("./index").TableResource;
  };
}
