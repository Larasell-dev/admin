import type { ComponentType } from "react";

type AdminWindow = Window & {
  LarasellAdmin: {
    Components: {
      Page: { Header: ComponentType<any>; Body: ComponentType<any> };
      ResourceTable: ComponentType<any>;
    };
  };
};

const components = (window as unknown as AdminWindow).LarasellAdmin.Components;

export const { Page, ResourceTable } = components;
