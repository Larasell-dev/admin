import { Button, EmptyState } from "@larasell-dev/ui";
import { BugLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { Component, type ReactNode } from "react";

type Props = {
  children: ReactNode;
  /** Wraps the crashed state, so it can fill a page or the whole screen. */
  frame: (crashed: ReactNode) => ReactNode;
  /** Shows the children again once it changes, such as the page's address. */
  resetKey?: unknown;
};

/** Shows a crashed state instead of a frontend error that would leave the screen blank. */
export class ErrorBoundary extends Component<Props, { failed: boolean; resetKey: unknown }> {
  state = { failed: false, resetKey: this.props.resetKey };

  static getDerivedStateFromError() {
    return { failed: true };
  }

  static getDerivedStateFromProps(props: Props, state: { resetKey: unknown }) {
    return props.resetKey === state.resetKey ? null : { failed: false, resetKey: props.resetKey };
  }

  render() {
    if (!this.state.failed) {
      return this.props.children;
    }

    return this.props.frame(
      <EmptyState
        icon={BugLine}
        title={fbs(
          "Something went wrong",
          "Heading shown when part of the panel crashed in the browser and can't be shown",
        )}
        description={fbs(
          "This part of the panel ran into a problem. Reload the page to try again.",
          "Text below the 'Something went wrong' heading, shown when part of the panel crashed in the browser",
        )}
      >
        <Button onClick={() => window.location.reload()}>
          <fbt desc="Button below a crashed part of the panel that reloads the page">
            Reload page
          </fbt>
        </Button>
      </EmptyState>,
    );
  }
}
