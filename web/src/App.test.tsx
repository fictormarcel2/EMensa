import {
  configure,
  act,
  render,
  screen,
  fireEvent,
  waitFor,
  cleanup,
  within,
} from "@testing-library/react";
import { afterEach, beforeEach, it, expect, vi } from "vitest";
import App from "./App";
import { api } from "./api";
import type { Plan } from "./api";
vi.mock("./api", async () => {
  const actual = await vi.importActual("./api");
  return { ...actual, api: vi.fn() };
});
const mocked = vi.mocked(api);
beforeEach(() => {
  configure({ asyncUtilTimeout: 15000 });
  history.replaceState(null, "", "/");
  mocked.mockImplementation(async (path, method) => {
    if (path === "/bootstrap")
      return {
        csrfToken: "token",
        user: null,
        mensas: [
          {
            id: "eupener",
            name: "Mensa Eupener Straße",
            hours: {},
            meetingPoints: [{ id: "entrance", name: "Main entrance" }],
          },
          { id: "academica", name: "Academica", hours: {}, meetingPoints: [] },
        ],
        today: "2026-11-06",
        demoAccounts: [
          { alias: "Alex", email: "alex@example.test", password: "evaluation" },
        ],
      } as never;
    if (path.startsWith("/overview"))
      return {
        date: "2026-11-06",
        mensaId: "eupener",
        slots: [
          {
            id: "12:15",
            time: "12:15",
            end: "12:30",
            selectable: true,
            count: 0,
            friends: [],
            publicParticipants: [],
          },
        ],
        serviceWindows: [{ start: "11:15", end: "14:00" }],
        menu: [],
        menuNotice: "Sample menu",
        hoursVerifiedAt: "2026-11-05",
      } as never;
    if (path.startsWith("/plans")) return { plan: null } as never;
    if (path === "/auth/login" && method === "POST")
      return { user: { id: 1, alias: "Alex" }, csrfToken: "newtoken" } as never;
    throw Error("Unexpected " + path);
  });
});
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});
it("does not refresh an old lunch context when a pending friend action completes", async () => {
  const original = mocked.getMockImplementation()!;
  let finish!: (value: unknown) => void;
  const pending = new Promise((resolve) => {
    finish = resolve;
  });
  mocked.mockImplementation(async (path, method, body) => {
    if (path === "/bootstrap") {
      const result = (await original(path, method, body)) as unknown as Record<
        string,
        unknown
      >;
      return { ...result, user: { id: 1, alias: "Alex" } } as never;
    }
    if (path === "/friends/accept") return pending as never;
    if (path.startsWith("/friends?"))
      return {
        friends: [],
        requests: [{ id: 2, alias: "Pending friend" }],
        outgoing: [],
      } as never;
    return original(path, method, body);
  });
  render(<App />);
  await screen.findByRole("radio");
  fireEvent.click(screen.getAllByRole("button", { name: "Friends" })[0]);
  fireEvent.click(
    await screen.findByRole("button", { name: "Accept request" }),
  );
  fireEvent.change(screen.getByLabelText("Lunch date"), {
    target: { value: "2026-11-09" },
  });
  await waitFor(() =>
    expect(
      mocked.mock.calls.some(
        ([path]) => path.startsWith("/friends?") && path.includes("2026-11-09"),
      ),
    ).toBe(true),
  );
  const reads = mocked.mock.calls.filter(([path]) =>
    path.startsWith("/overview"),
  ).length;
  await act(async () => finish({}));
  expect(
    mocked.mock.calls.filter(([path]) => path.startsWith("/overview")),
  ).toHaveLength(reads);
});
it("preserves one draft across sign-in without saving, then clears it when the mensa changes", async () => {
  render(<App />);
  const radio = await screen.findByRole("radio");
  fireEvent.click(radio);
  fireEvent.change(screen.getByLabelText(/Meet-up point/), {
    target: { value: "entrance" },
  });
  fireEvent.change(screen.getByLabelText(/Who can see/), {
    target: { value: "friends" },
  });
  fireEvent.click(screen.getByRole("checkbox"));
  fireEvent.click(screen.getByRole("button", { name: "Sign in to confirm" }));
  fireEvent.change(screen.getByLabelText("Evaluation account"), {
    target: { value: "alex@example.test" },
  });
  fireEvent.click(
    within(screen.getByRole("dialog")).getByRole("button", {
      name: "Sign in",
    }),
  );
  await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
  await waitFor(() =>
    expect((screen.getByRole("radio") as HTMLInputElement).checked).toBe(true),
  );
  expect(mocked.mock.calls.some(([, method]) => method === "PUT")).toBe(false);
  expect(
    (screen.getByLabelText(/Meet-up point/) as HTMLSelectElement).value,
  ).toBe("entrance");
  expect(
    (screen.getByLabelText(/Who can see/) as HTMLSelectElement).value,
  ).toBe("friends");
  expect((screen.getByRole("checkbox") as HTMLInputElement).checked).toBe(true);
  fireEvent.change(screen.getByLabelText("Mensa"), {
    target: { value: "academica" },
  });
  await waitFor(() =>
    expect((screen.getByRole("radio") as HTMLInputElement).checked).toBe(false),
  );
});
it("closes the sign-in dialog with Escape", async () => {
  render(<App />);
  await screen.findByRole("radio");
  fireEvent.click(screen.getByRole("button", { name: "Sign in" }));
  expect(screen.getByRole("dialog")).toBeTruthy();
  fireEvent.keyDown(document, { key: "Escape" });
  expect(screen.queryByRole("dialog")).toBeNull();
});
it("keeps the complete guest draft when login loads an existing owner plan and saves with its version", async () => {
  const original = mocked.getMockImplementation()!;
  let saved: Plan = {
    id: 9,
    date: "2026-11-06",
    mensaId: "eupener",
    slotId: "12:00",
    time: "12:00",
    end: "12:15",
    meetingPointId: null,
    visibility: "public",
    reminder: false,
    reminderStatus: "off",
    status: "confirmed",
    version: 7,
    editable: true,
  };
  mocked.mockImplementation(async (path, method, body) => {
    if (path.startsWith("/plans")) {
      if (method === "PUT") {
        const submitted = body as Partial<Plan>;
        saved = {
          ...saved,
          ...submitted,
          time: submitted.slotId || saved.time,
          version: 8,
        };
      }
      return { plan: saved } as never;
    }
    return original(path, method, body);
  });
  render(<App />);
  fireEvent.click(await screen.findByRole("radio"));
  fireEvent.change(screen.getByLabelText(/Meet-up point/), {
    target: { value: "entrance" },
  });
  fireEvent.change(screen.getByLabelText(/Who can see/), {
    target: { value: "friends" },
  });
  fireEvent.click(screen.getByRole("checkbox"));
  fireEvent.click(screen.getByRole("button", { name: "Sign in to confirm" }));
  fireEvent.change(screen.getByLabelText("Evaluation account"), {
    target: { value: "alex@example.test" },
  });
  fireEvent.click(
    within(screen.getByRole("dialog")).getByRole("button", { name: "Sign in" }),
  );
  const confirm = await screen.findByRole("button", { name: "Save changes" });
  expect((screen.getByRole("radio") as HTMLInputElement).checked).toBe(true);
  expect(
    (screen.getByLabelText(/Meet-up point/) as HTMLSelectElement).value,
  ).toBe("entrance");
  expect(
    (screen.getByLabelText(/Who can see/) as HTMLSelectElement).value,
  ).toBe("friends");
  expect((screen.getByRole("checkbox") as HTMLInputElement).checked).toBe(true);
  expect(mocked.mock.calls.some(([, method]) => method === "PUT")).toBe(false);
  fireEvent.click(confirm);
  await waitFor(() =>
    expect(mocked).toHaveBeenCalledWith(
      "/plans",
      "PUT",
      expect.objectContaining({
        version: 7,
        slotId: "12:15",
        meetingPointId: "entrance",
        visibility: "friends",
        reminder: true,
      }),
    ),
  );
  expect(await screen.findByText("Your lunch plan is confirmed.")).toBeTruthy();
});

it.each(["resolve", "reject"])(
  "ignores a stale save %s after changing lunch date",
  async (outcome) => {
    const original = mocked.getMockImplementation()!;
    let finish!: (value: unknown) => void;
    let fail!: (reason: Error) => void;
    const pending = new Promise((resolve, reject) => {
      finish = resolve;
      fail = reject;
    });
    mocked.mockImplementation(async (path, method, body) => {
      if (path === "/bootstrap") {
        const result = (await original(
          path,
          method,
          body,
        )) as unknown as Record<string, unknown>;
        return { ...result, user: { id: 1, alias: "Alex" } } as never;
      }
      if (method === "PUT") return pending as never;
      return original(path, method, body);
    });
    render(<App />);
    fireEvent.click(await screen.findByRole("radio"));
    fireEvent.click(screen.getByRole("button", { name: "Confirm my plan" }));
    await waitFor(() =>
      expect(mocked.mock.calls.some(([, method]) => method === "PUT")).toBe(
        true,
      ),
    );
    fireEvent.change(screen.getByLabelText("Lunch date"), {
      target: { value: "2026-11-09" },
    });
    await screen.findByRole("radio");
    await act(async () => {
      if (outcome === "reject") fail(new Error("Old save failed"));
      else
        finish({
          plan: {
            date: "2026-11-06",
            mensaId: "eupener",
            slotId: "12:15",
            version: 99,
            status: "confirmed",
          },
        });
    });
    expect(screen.queryByText("Old save failed")).toBeNull();
    expect(screen.queryByText("Your lunch plan is confirmed.")).toBeNull();
    expect(
      screen.getByRole("heading", { name: "Make time for lunch." }),
    ).toBeTruthy();
    expect((screen.getByRole("radio") as HTMLInputElement).checked).toBe(false);
  },
);
