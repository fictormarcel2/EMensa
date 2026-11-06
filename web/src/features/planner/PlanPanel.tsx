import { useEffect, useState } from "react";
import { Bell, CheckCircle2, MapPin, ShieldCheck } from "lucide-react";
import { api, type Mensa, type Plan, type Slot } from "../../api";
export type Draft = {
  meetingPointId: string | null;
  visibility: "private" | "friends" | "public";
  reminder: boolean;
};
export function PlanPanel({
  plan,
  selected,
  mensa,
  date,
  slot,
  onSave,
  onCancel,
  busy,
  authenticated,
  onLogin,
  preserveDraft = false,
}: {
  plan: Plan | null;
  selected: string | null;
  mensa: Mensa;
  date: string;
  slot?: Slot;
  onSave: (draft: Draft) => void;
  onCancel: () => void;
  busy: boolean;
  authenticated: boolean;
  onLogin: () => void;
  preserveDraft?: boolean;
}) {
  const [meeting, setMeeting] = useState("");
  const [visibility, setVisibility] = useState<Draft["visibility"]>("private");
  const [reminder, setReminder] = useState(false);
  const [pushMessage, setPushMessage] = useState("");
  const [confirmCancel, setConfirmCancel] = useState(false);
  useEffect(() => {
    if (preserveDraft) return;
    setMeeting(
      plan?.mensaId === mensa.id &&
        mensa.meetingPoints.some((p) => p.id === plan.meetingPointId)
        ? plan.meetingPointId || ""
        : "",
    );
    setVisibility(plan?.visibility || "private");
    setReminder(plan?.reminder || false);
    setConfirmCancel(false);
  }, [plan?.id, plan?.version, date, mensa.id, preserveDraft]);
  const active = plan && plan.status !== "cancelled";
  const unchanged =
    active &&
    plan.slotId === selected &&
    plan.mensaId === mensa.id &&
    plan.meetingPointId === (meeting || null) &&
    plan.visibility === visibility &&
    plan.reminder === reminder;
  async function enablePush() {
    try {
      if (
        !("Notification" in window) ||
        !("serviceWorker" in navigator) ||
        !("PushManager" in window)
      ) {
        setPushMessage(
          "Web Push is not supported in this browser. No device reminder is enabled.",
        );
        return;
      }
      const config = await api<{
        supported: boolean;
        publicKey: string | null;
      }>("/push/config");
      if (!config.supported || !config.publicKey) {
        setPushMessage(
          "Push delivery is not configured on this server. No device notification will be delivered.",
        );
        return;
      }
      const permission = await Notification.requestPermission();
      if (permission !== "granted") {
        setPushMessage(
          "Notifications are blocked. You can change this in your browser settings.",
        );
        return;
      }
      const registration = await navigator.serviceWorker.register("/sw.js");
      await navigator.serviceWorker.ready;
      const base64 = config.publicKey.replace(/-/g, "+").replace(/_/g, "/");
      const bytes = Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
      const subscription =
        (await registration.pushManager.getSubscription()) ||
        (await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: bytes,
        }));
      await api("/push/subscriptions", "POST", subscription.toJSON());
      setPushMessage(
        "This browser is connected for reminders. Delivery depends on browser and network availability.",
      );
      setReminder(true);
    } catch (error) {
      setPushMessage(
        error instanceof Error
          ? error.message
          : "Could not enable notifications.",
      );
    }
  }
  return (
    <aside className="panel plan-panel">
      <div className="plan-title">
        <span className="round-icon">
          <MapPin size={21} />
        </span>
        <h2>Your lunch plan</h2>
      </div>
      {active && (
        <div className="saved-banner">
          <CheckCircle2 size={18} />
          <span>
            Saved · {plan.time} at{" "}
            {plan.mensaId === mensa.id ? mensa.name : "another mensa"}
            <small>
              {plan.status === "expired"
                ? "Arrival window ended"
                : plan.editable
                  ? "You can edit before arrival"
                  : "This plan is now read-only"}
            </small>
          </span>
        </div>
      )}
      {active && plan.reminder && plan.reminderStatus === "too_late" && (
        <p className="sample-note" role="status">
          Your arrival is within 15 minutes. No retrospective reminder is scheduled.
        </p>
      )}
      <div className="draft-time">
        <span>
          {unchanged ? "Your confirmed arrival" : "Your draft · not saved yet"}
        </span>
        <strong>{selected || "Choose a time"}</strong>
        <small>
          {new Date(date + "T12:00:00").toLocaleDateString("en-GB", {
            weekday: "long",
            day: "numeric",
            month: "long",
          })}
        </small>
      </div>
      {slot && (
        <p className="overlap">
          {slot.friends.length
            ? `${slot.friends.map((f) => f.alias).join(", ")} ${slot.friends.length === 1 ? "has" : "have"} a confirmed plan at this time.`
            : "No friends have shared a confirmed plan at this time."}
        </p>
      )}
      <label className="field">
        Meet-up point <span>Optional · a static meeting place</span>
        <select
          value={meeting}
          disabled={!!active && !plan.editable}
          onChange={(e) => setMeeting(e.target.value)}
        >
          <option value="">No meeting point</option>
          {mensa.meetingPoints.map((p) => (
            <option key={p.id} value={p.id}>
              {p.name}
            </option>
          ))}
        </select>
      </label>
      <label className="field">
        Who can see your plan?
        <select
          value={visibility}
          disabled={!!active && !plan.editable}
          onChange={(e) => setVisibility(e.target.value as Draft["visibility"])}
        >
          <option value="private">Private · anonymous count only</option>
          <option value="friends">Accepted friends</option>
          <option value="public">Public · share my alias</option>
        </select>
      </label>
      <p className="privacy-copy">
        <ShieldCheck size={15} />{" "}
        {visibility === "private"
          ? "Your identity and meeting point stay private."
          : visibility === "friends"
            ? "Accepted friends can see your time and meeting point."
            : "Anyone can see your alias and time. Your meeting point stays private."}
      </p>
      <label className="toggle-row">
        <Bell size={18} />
        <span>
          Remind me 15 minutes before
          <small>Requires this device’s permission</small>
        </span>
        <input
          type="checkbox"
          checked={reminder}
          disabled={!!active && !plan.editable}
          onChange={(e) => setReminder(e.target.checked)}
        />
      </label>
      {reminder && (
        <>
          <button className="text-button" type="button" onClick={enablePush}>
            Enable device notifications
          </button>
          <p className="fine-print">
            {pushMessage ||
              "A reminder preference alone does not enable notifications. Connect this browser to receive Web Push."}
          </p>
        </>
      )}
      <button
        className="primary full"
        disabled={
          busy || !selected || !slot?.selectable || (!!active && !plan.editable)
        }
        onClick={() =>
          authenticated
            ? onSave({ meetingPointId: meeting || null, visibility, reminder })
            : onLogin()
        }
      >
        {busy
          ? "Saving…"
          : !authenticated
            ? "Sign in to confirm"
            : active
              ? "Save changes"
              : "Confirm my plan"}
      </button>
      {active &&
        plan.editable &&
        (confirmCancel ? (
          <div className="cancel-confirm">
            <p>Cancel your saved lunch plan?</p>
            <button className="danger" disabled={busy} onClick={onCancel}>
              Yes, cancel plan
            </button>
            <button
              className="text-button"
              onClick={() => setConfirmCancel(false)}
            >
              Keep plan
            </button>
          </div>
        ) : (
          <button
            className="text-button full"
            onClick={() => setConfirmCancel(true)}
          >
            Cancel saved plan
          </button>
        ))}
      <p className="fine-print">
        One plan per day. This is a lunch plan, not a table reservation.
      </p>
    </aside>
  );
}
