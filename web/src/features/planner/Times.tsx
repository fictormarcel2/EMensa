import type { Slot } from "../../api";
export function Times({
  slots,
  selected,
  onSelect,
  compact = false,
}: {
  slots: Slot[];
  selected: string | null;
  onSelect: (id: string) => void;
  compact?: boolean;
}) {
  const max = Math.max(0, ...slots.map((s) => s.count));
  return (
    <section className="panel times-panel">
      <div className="section-heading">
        <div>
          <h2>Find your lunch time</h2>
          <p>One time. A little more together.</p>
        </div>
        <span className="subtle-tag">15 min arrival slots</span>
      </div>
      <p className="explanation">
        These are submitted plans, not live crowd or queue measurements. Longest
        bar: {max} plans.
      </p>
      {slots.length === 0 ? (
        <div className="empty">
          No service slots for this date. Try another weekday.
        </div>
      ) : (
        <fieldset className={`slot-list ${compact ? "compact" : ""}`}>
          <legend className="sr-only">Choose one arrival time</legend>
          {slots.map((s) => (
            <label
              key={s.id}
              className={`slot-row ${selected === s.id ? "selected" : ""} ${!s.selectable ? "past" : ""}`}
            >
              <input
                type="radio"
                name="arrival-time"
                value={s.id}
                checked={selected === s.id}
                disabled={!s.selectable}
                onChange={() => onSelect(s.id)}
              />
              <span className="slot-time">
                {s.time}
                <small>{s.selectable ? "Arrival" : "Past / unavailable"}</small>
              </span>
              <span className="bar-track" aria-hidden="true">
                <span style={{ width: `${(s.count / (max || 1)) * 100}%` }} />
              </span>
              <span className="slot-count">
                {s.count}
                <small>{s.count === 1 ? "plan" : "plans"}</small>
              </span>
              <span className="friend-count">
                {s.friends.length > 0
                  ? `${s.friends.length} ${s.friends.length === 1 ? "friend" : "friends"}`
                  : "—"}
                {s.friends.length > 0 && (
                  <small>{s.friends.map((f) => f.alias).join(", ")}</small>
                )}
              </span>
            </label>
          ))}
        </fieldset>
      )}
    </section>
  );
}
