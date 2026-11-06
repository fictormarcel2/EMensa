self.addEventListener("push", (event) => {
  let data = {};
  try {
    data = event.data.json();
  } catch {}
  event.waitUntil(
    self.registration.showNotification(data.title || "Your lunch plan", {
      body: data.body || "Open MensaTogether to check your current lunch plan.",
      tag: data.tag || "mensatogether-reminder",
      data: { url: data.url || "/?view=plan" },
    }),
  );
});
self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const url = new URL(
    event.notification.data?.url || "/?view=plan",
    self.location.origin,
  );
  if (url.origin !== self.location.origin) return;
  event.waitUntil(
    clients
      .matchAll({ type: "window", includeUncontrolled: true })
      .then(async (windows) => {
        for (const client of windows) {
          if ("navigate" in client) {
            await client.navigate(url.href);
            return client.focus();
          }
        }
        return clients.openWindow(url.href);
      }),
  );
});
