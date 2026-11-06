import {
  render,
  screen,
  fireEvent,
  waitFor,
  cleanup,
  act,
} from "@testing-library/react";
import { afterEach, beforeEach, it, expect, vi } from "vitest";
import { Friends } from "./Friends";
import { api } from "../../api";
vi.mock("../../api", () => ({ api: vi.fn() }));
const mocked = vi.mocked(api);
beforeEach(() => {
  mocked.mockImplementation(
    async () => ({ friends: [], requests: [], outgoing: [] }) as never,
  );
});
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
  vi.unstubAllGlobals();
});
it("never sends a changed code using an alias from an older pending preview", async () => {
  let resolve!: (value: { alias: string }) => void;
  mocked.mockImplementation(async (path) =>
    path.startsWith("/invites?")
      ? (new Promise((r) => {
          resolve = r;
        }) as never)
      : ({ friends: [], requests: [], outgoing: [] } as never),
  );
  render(<Friends date="2026-11-06" mensas={[]} onChanged={() => {}} />);
  const input = screen.getByLabelText(/enter an invitation/);
  fireEvent.change(input, { target: { value: "first-code" } });
  fireEvent.click(screen.getByRole("button", { name: "Preview friend" }));
  fireEvent.change(input, { target: { value: "second-code" } });
  await act(async () => resolve({ alias: "Old alias" }));
  expect(
    screen.queryByRole("button", { name: "Send friend request" }),
  ).toBeNull();
  expect(mocked.mock.calls.some(([, method]) => method === "POST")).toBe(false);
});
it("stops a camera stream granted after the component unmounts", async () => {
  let resolve!: (value: MediaStream) => void;
  const stop = vi.fn();
  const getUserMedia = vi.fn(
    () =>
      new Promise<MediaStream>((r) => {
        resolve = r;
      }),
  );
  Object.defineProperty(navigator, "mediaDevices", {
    configurable: true,
    value: { getUserMedia },
  });
  vi.stubGlobal(
    "BarcodeDetector",
    class {
      detect() {
        return Promise.resolve([]);
      }
    },
  );
  const view = render(
    <Friends date="2026-11-06" mensas={[]} onChanged={() => {}} />,
  );
  fireEvent.click(
    screen.getByRole("button", { name: "Scan a friend’s QR code" }),
  );
  await waitFor(() => expect(getUserMedia).toHaveBeenCalled());
  view.unmount();
  await act(async () =>
    resolve({ getTracks: () => [{ stop }] } as unknown as MediaStream),
  );
  expect(stop).toHaveBeenCalledOnce();
});
it("keeps manual code entry available when camera permission is denied", async () => {
  Object.defineProperty(navigator, "mediaDevices", {
    configurable: true,
    value: {
      getUserMedia: vi
        .fn()
        .mockRejectedValue(new DOMException("Denied", "NotAllowedError")),
    },
  });
  vi.stubGlobal(
    "BarcodeDetector",
    class {
      detect() {
        return Promise.resolve([]);
      }
    },
  );
  render(<Friends date="2026-11-06" mensas={[]} onChanged={() => {}} />);
  fireEvent.click(
    screen.getByRole("button", { name: "Scan a friend’s QR code" }),
  );
  expect(await screen.findByRole("alert")).toHaveProperty(
    "textContent",
    expect.stringContaining("enter the invitation code"),
  );
  expect(screen.getByLabelText(/enter an invitation/)).toBeTruthy();
});

it.each(["resolve", "reject"])(
  "ignores the previous date's friends %s after a date change",
  async (outcome) => {
    let finish!: (value: unknown) => void;
    let fail!: (reason: Error) => void;
    const pending = new Promise((resolve, reject) => {
      finish = resolve;
      fail = reject;
    });
    mocked.mockImplementation(async (path) => {
      if (path.endsWith("2026-11-06")) return pending as never;
      return {
        friends: [{ id: 2, alias: "Current friend", sharedPlan: null }],
        requests: [],
        outgoing: [],
      } as never;
    });
    const view = render(
      <Friends date="2026-11-06" mensas={[]} onChanged={() => {}} />,
    );
    view.rerender(
      <Friends date="2026-11-09" mensas={[]} onChanged={() => {}} />,
    );
    await screen.findByText("Current friend");
    await act(async () => {
      if (outcome === "reject") fail(new Error("Old date failed"));
      else
        finish({
          friends: [{ id: 1, alias: "Old friend", sharedPlan: null }],
          requests: [],
          outgoing: [],
        });
    });
    expect(screen.getByText("Current friend")).toBeTruthy();
    expect(screen.queryByText("Old friend")).toBeNull();
    expect(screen.queryByText("Old date failed")).toBeNull();
  },
);
