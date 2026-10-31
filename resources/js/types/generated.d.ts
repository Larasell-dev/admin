declare namespace Larasell.Admin.Data.View {
  export type AdminsPageData = {
    createUrl: string;
    admins: import("./index").TableResource;
  };
  export type CategoriesPageData = {
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createUrl: string;
  };
  export type CategoryPageData = {
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createUrl: string;
    category: Larasell.Admin.Data.ViewModel.CategoryDetailData;
    updateUrl: string;
    products: import("./index").TableResource;
    availableProductsUrl: string;
    addProductUrl: string;
    createProductUrl: string | null;
  };
  export type CreateAdminPageData = {
    storeUrl: string;
    indexUrl: string;
  };
  export type CreateCategoryPageData = {
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createUrl: string;
    storeUrl: string;
    indexUrl: string;
    parent: Larasell.Admin.Data.ViewModel.CategoryDetailData | null;
    createProductUrl: string | null;
  };
  export type CreateProductPageData = {
    storeUrl: string;
    indexUrl: string;
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createCategoryUrl: string;
    uploadImagesUrl: string;
  };
  export type EditAdminPageData = {
    editedAdmin: Larasell.Admin.Data.ViewModel.AdminData;
    updateUrl: string;
    indexUrl: string;
  };
  export type EditProductPageData = {
    product: Larasell.Admin.Data.ViewModel.ProductDetailData;
    updateUrl: string;
    indexUrl: string;
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createCategoryUrl: string;
    uploadImagesUrl: string;
  };
  export type HomePageData = {
    createProductUrl: string | null;
    createCategoryUrl: string;
    inviteAdminUrl: string;
    helpEmail: string;
  };
  export type LoginPageData = {
    loginUrl: string;
    devLoginUrl: string | null;
  };
  export type MissingCategoryPageData = {
    categories: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
    createUrl: string;
    category: null;
  };
  export type ProfilePageData = {
    admin: Larasell.Admin.Data.ViewModel.AdminData;
    updateUrl: string;
    avatarUploadUrl: string;
  };
}
declare namespace Larasell.Admin.Data.View.ApiTokens {
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
}
declare namespace Larasell.Admin.Data.View.Customers {
  export type CreateCustomerPageData = {
    storeUrl: string;
    indexUrl: string;
  };
  export type EditCustomerPageData = {
    customer: Larasell.Admin.Data.ViewModel.Customers.CustomerDetailData;
    updateUrl: string;
    indexUrl: string;
    orders: import("./index").TableResource;
  };
}
declare namespace Larasell.Admin.Data.View.Orders {
  export type OrderPageData = {
    order: Larasell.Admin.Data.ViewModel.Orders.OrderDetailData;
    indexUrl: string;
    customerUrl: string | null;
  };
}
declare namespace Larasell.Admin.Data.ViewModel {
  export type AdminData = {
    name: string;
    email: string;
    avatarUrl: string | null;
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
    children: Array<Larasell.Admin.Data.ViewModel.CategoryData>;
  };
  export type CategoryDetailData = {
    id: number;
    name: string;
    slug: string;
    visible: boolean;
  };
  export type DimensionsData = {
    length: string;
    width: string;
    height: string;
    unit: string;
  };
  export type NavigationItemData = {
    id: string;
    label: string;
    url: string;
    icon: string;
    children: Array<Larasell.Admin.Data.ViewModel.NavigationItemData>;
  };
  export type ProductDetailData = {
    id: number;
    name: string;
    slug: string;
    visible: boolean;
    weight: Larasell.Admin.Data.ViewModel.WeightData | null;
    dimensions: Larasell.Admin.Data.ViewModel.DimensionsData | null;
    sku: string | null;
    barcode: string | null;
    stock: number | null;
    allowBackorders: boolean;
    minQuantity: number | null;
    maxQuantity: number | null;
    categoryIds: Array<number>;
    images: Array<Larasell.Admin.Data.ViewModel.ProductImageData>;
  };
  export type ProductImageData = {
    id: number;
    url: string;
    filename: string;
    alt: string | null;
  };
  export type WeightData = {
    amount: string;
    unit: string;
  };
}
declare namespace Larasell.Admin.Data.ViewModel.ApiTokens {
  export type CreatedApiTokenData = {
    name: string;
    plainTextToken: string;
  };
}
declare namespace Larasell.Admin.Data.ViewModel.Customers {
  export type CustomerDetailData = {
    name: string;
    firstName: string;
    lastName: string;
  };
}
declare namespace Larasell.Admin.Data.ViewModel.Orders {
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
    status: Larasell.Admin.Data.ViewModel.Orders.BadgeData;
    placedAt: string;
    items: Array<Larasell.Admin.Data.ViewModel.Orders.OrderItemData>;
    subtotal: string;
    discount: string | null;
    shipping: Larasell.Admin.Data.ViewModel.Orders.AmountData | null;
    taxes: Array<Larasell.Admin.Data.ViewModel.Orders.AmountData>;
    taxIncluded: boolean;
    total: string;
    customerName: string;
    customerEmail: string;
    customerPhone: string | null;
    billingAddress: Larasell.Admin.Data.ViewModel.Orders.AddressData | null;
    shippingAddress: Larasell.Admin.Data.ViewModel.Orders.AddressData | null;
    payments: Array<Larasell.Admin.Data.ViewModel.Orders.PaymentData>;
  };
  export type OrderItemData = {
    name: string;
    variant: string | null;
    quantity: number;
    unitPrice: string;
    total: string;
  };
  export type PaymentData = {
    method: string;
    status: Larasell.Admin.Data.ViewModel.Orders.BadgeData;
    reference: string | null;
    amount: string;
  };
}
