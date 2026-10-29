import * as Inertia from "@inertiajs/react";
import type * as AdminApi from "@larasell-dev/admin";
import * as UI from "@larasell-dev/ui";
import * as React from "react";
import * as JsxDevRuntime from "react/jsx-dev-runtime";
import * as JsxRuntime from "react/jsx-runtime";
import { Page } from "./Components/Page";
import ResourceTable from "./Components/ResourceTable";
import { Larasell } from "./extensions";
import SidebarLayout from "./Layouts/SidebarLayout";

/**
 * The panel's copies of the modules that extensions and app pages import. The admin Vite plugin
 * points those imports here, so their bundles hold only their own code and a single React runs
 * the page. Everything @larasell-dev/admin exports is public API.
 */
export const sharedModules = {
  react: React,
  "react/jsx-runtime": JsxRuntime,
  "react/jsx-dev-runtime": JsxDevRuntime,
  "@inertiajs/react": Inertia,
  "@larasell-dev/ui": UI,
  // Checked against the types extensions see in resources/vite/client.d.ts.
  "@larasell-dev/admin": { Larasell, Page, ResourceTable, SidebarLayout } satisfies typeof AdminApi,
};
