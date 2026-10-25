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
  };
  export type CreateProductPageData = {
    storeUrl: string;
    indexUrl: string;
  };
  export type CustomersPageData = {
    customers: Larasell.Admin.Data.ViewModel.PaginatedCustomersData;
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
  };
  export type HomePageData = {
    admin: Larasell.Admin.Data.ViewModel.AdminData;
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
  export type CustomerData = {
    id: number;
    name: string;
    ordersCount: number;
    createdAt: string | null;
  };
  export type NavigationItemData = {
    id: string;
    label: string;
    url: string;
    icon: string;
    children: Array<Larasell.Admin.Data.ViewModel.NavigationItemData>;
  };
  export type PaginatedCustomersData = {
    data: Array<Larasell.Admin.Data.ViewModel.CustomerData>;
    current_page: number;
    per_page: number;
    total: number;
  };
  export type ProductDetailData = {
    id: number;
    name: string;
    slug: string;
    visible: boolean;
  };
}
