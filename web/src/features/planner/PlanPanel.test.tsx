import { render, screen, fireEvent, cleanup } from "@testing-library/react";
import { afterEach, it, expect, vi } from "vitest";
import { PlanPanel } from "./PlanPanel";
import type { Mensa, Plan, Slot } from "../../api";
afterEach(cleanup);
const mensa: Mensa = {
  id: "eupener",
  name: "Mensa Eupener Straße",
  hours: {},
  meetingPoints: [{ id: "entrance", name: "Main entrance" }],
};
const slot: Slot = {
  id: "12:15",
  time: "12:15",
  end: "12:30",
  selectable: true,
  count: 0,
  friends: [],
  publicParticipants: [],
};
it("never saves merely by selecting a meeting place, and sends one explicit draft on confirmation", () => {
  const save = vi.fn();
  render(
    <PlanPanel
      plan={null}
      selected="12:15"
      mensa={mensa}
      date="2026-11-06"
      slot={slot}
      onSave={save}
      onCancel={() => {}}
      busy={false}
      authenticated
      onLogin={() => {}}
    />,
  );
  fireEvent.change(screen.getByLabelText(/Meet-up point/), {
    target: { value: "entrance" },
  });
  expect(save).not.toHaveBeenCalled();
  fireEvent.click(screen.getByRole("button", { name: "Confirm my plan" }));
  expect(save).toHaveBeenCalledWith({
    meetingPointId: "entrance",
    visibility: "private",
    reminder: false,
  });
});
it("does not restore a meeting point from another mensa", () => {
  const plan: Plan = {
    id: 1,
    date: "2026-11-06",
    mensaId: "academica",
    slotId: "12:15",
    time: "12:15",
    end: "12:30",
    meetingPointId: "other-place",
    visibility: "private",
    reminder: false,
    reminderStatus: "off",
    status: "confirmed",
    version: 1,
    editable: true,
  };
  render(
    <PlanPanel
      plan={plan}
      selected="12:15"
      mensa={mensa}
      date="2026-11-06"
      slot={slot}
      onSave={() => {}}
      onCancel={() => {}}
      busy={false}
      authenticated
      onLogin={() => {}}
    />,
  );
  expect(
    (screen.getByLabelText(/Meet-up point/) as HTMLSelectElement).value,
  ).toBe("");
});
