import type { ComponentType } from "react";

type AdminWindow = Window & {
  LarasellAdmin: {
    Components: {
      ResourceTable: ComponentType<any>;
    };
  };
};

export const ResourceTable = (window as unknown as AdminWindow).LarasellAdmin.Components
  .ResourceTable;
