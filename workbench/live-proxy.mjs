// Serves the playground and Reverb from one origin, so a single portal (or tunnel) carries both
// the panel and the websocket live updates use: `node workbench/live-proxy.mjs <port>`.
// Reverb's websocket (/app/…) goes to 127.0.0.1:8080, everything else to the panel on 8000.
import http from "node:http";
import net from "node:net";

const port = Number(process.argv[2] ?? process.env.PORT ?? 8001);
const target = (url) => (url.startsWith("/app/") ? 8080 : 8000);

const server = http.createServer((request, response) => {
  const upstream = http.request(
    {
      host: "127.0.0.1",
      port: target(request.url),
      method: request.method,
      path: request.url,
      headers: request.headers,
    },
    (reply) => {
      response.writeHead(reply.statusCode ?? 502, reply.headers);
      reply.pipe(response);
    },
  );
  upstream.on("error", () => response.writeHead(502).end());
  request.pipe(upstream);
});

server.on("upgrade", (request, socket, head) => {
  const upstream = net.connect(target(request.url), "127.0.0.1", () => {
    const headers = Object.entries(request.headers).map(([name, value]) => `${name}: ${value}`);
    upstream.write([`${request.method} ${request.url} HTTP/1.1`, ...headers, "", ""].join("\r\n"));
    upstream.write(head);
    socket.pipe(upstream).pipe(socket);
  });
  upstream.on("error", () => socket.destroy());
  socket.on("error", () => upstream.destroy());
});

server.listen(port, "127.0.0.1");
