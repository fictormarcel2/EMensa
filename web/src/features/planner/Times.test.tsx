import { render, screen, fireEvent, cleanup } from "@testing-library/react";
import { afterEach, describe, it, expect } from "vitest";
import { useState } from "react";
import { Times } from "./Times";
import type { Slot } from "../../api";
afterEach(cleanup);
const slots: Slot[] = [
  {
    id: "12:00",
    time: "12:00",
    end: "12:15",
    selectable: true,
    count: 2,
    friends: [],
    publicParticipants: [],
  },
  {
    id: "12:15",
    time: "12:15",
    end: "12:30",
    selectable: true,
    count: 0,
    friends: [],
    publicParticipants: [],
  },
  {
    id: "14:15",
    time: "14:15",
    end: "14:30",
    selectable: false,
    count: 0,
    friends: [],
    publicParticipants: [],
  },
];
function Harness() {
  const [selected, setSelected] = useState<string | null>(null);
  return <Times slots={slots} selected={selected} onSelect={setSelected} />;
}
describe("single arrival choice", () => {
  it("replaces the first choice and prevents unavailable selection", () => {
    render(<Harness />);
    const radios = screen.getAllByRole("radio") as HTMLInputElement[];
    fireEvent.click(radios[0]);
    expect(radios[0].checked).toBe(true);
    fireEvent.click(radios[1]);
    expect(radios[0].checked).toBe(false);
    expect(radios[1].checked).toBe(true);
    expect(radios.filter((r) => r.checked)).toHaveLength(1);
    expect(radios[2].disabled).toBe(true);
  });
  it("offers text counts and explains what submissions mean", () => {
    render(<Harness />);
    expect(screen.getByText(/not live crowd/)).toBeTruthy();
    expect(screen.getAllByText("plans")).toHaveLength(3);
  });
});
