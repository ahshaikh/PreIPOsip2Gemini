export async function register() {
  if (process.env.NEXT_RUNTIME === 'nodejs') {
    await import('./sentry.server.config');
  }

  if (process.env.NEXT_RUNTIME === 'edge') {
    await import('./sentry.edge.config');
  }
}

export const onRequestError = async (
  err: Error,
  request: {
    path: string; // resource path, e.g. /blog?name=foo
    method: string;
    headers: { [key: string]: string };
  },
  context: {
    routerKind: 'Pages Router' | 'App Router';
    routePath: string; // routing path, e.g. /blog/[slug]
    routeType: 'render' | 'route' | 'action' | 'middleware';
    renderSource: 'react-server-components' | 'react-server-components-payload' | 'server-rendering';
    revalidateReason: 'on-demand' | 'stale' | undefined;
    renderType: 'dynamic' | 'dynamic-resume';
  }
) => {
  if (process.env.NEXT_PUBLIC_SENTRY_DSN) {
    const Sentry = await import('@sentry/nextjs');
    Sentry.captureException(err, {
      contexts: {
        nextjs: {
          request: {
            path: request.path,
            method: request.method,
          },
          router: context.routerKind,
          route: {
            path: context.routePath,
            type: context.routeType,
          },
        },
      },
    });
  }
};
