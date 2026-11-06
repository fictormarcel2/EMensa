import { useEffect, useRef, useState } from "react";
import {
  CalendarDays,
  Clock3,
  Users,
  Utensils,
  LogOut,
  ArrowRight,
  MapPin,
  Sun,
  Menu as MenuIcon,
  X,
  RefreshCw,
} from "lucide-react";
import {
  api,
  setCsrf,
  query,
  ApiError,
  type Bootstrap,
  type Overview,
  type Plan,
} from "./api";
import { Times } from "./features/planner/Times";
import { PlanPanel, type Draft } from "./features/planner/PlanPanel";
import { Menu } from "./features/menu/Menu";
import { Friends } from "./features/friends/Friends";
type View = "today" | "times" | "friends" | "plan";
const navigation = [
  { id: "today" as View, label: "Today", icon: Sun },
  { id: "times" as View, label: "Times", icon: Clock3 },
  { id: "friends" as View, label: "Friends", icon: Users },
  { id: "plan" as View, label: "My plan", icon: CalendarDays },
];
export default function App() {
  const [bootstrap, setBootstrap] = useState<Bootstrap | null>(null);
  const [overview, setOverview] = useState<Overview | null>(null);
  const [plan, setPlan] = useState<Plan | null>(null);
  const [view, setView] = useState<View>(() => {
    const value = new URLSearchParams(location.search).get("view");
    return ["today", "times", "friends", "plan"].includes(value || "")
      ? (value as View)
      : "today";
  });
  const [mensaId, setMensa] = useState("eupener");
  const [date, setDate] = useState("");
  const [selected, setSelected] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [preserveGuestDraft, setPreserveGuestDraft] = useState(false);
  const [busy, setBusy] = useState(false);
  const [login, setLogin] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [authError, setAuthError] = useState("");
  const [mobileMenu, setMobileMenu] = useState(false);
  const activeContext = useRef("");
  const generation = useRef(0);
  const reads = useRef(0);
  const contextIdentity = `${mensaId}|${date}|${bootstrap?.user?.id ?? "guest"}`;
  if (activeContext.current !== contextIdentity) {
    activeContext.current = contextIdentity;
    generation.current++;
  }
  const context = useRef("");
  const deepLinkUsed = useRef(false);
  const draft = useRef<string | null>(null);
  const mutation = useRef<{ key: string; id: string } | null>(null);
  const modalRef = useRef<HTMLElement>(null);
  draft.current = selected;
  useEffect(() => {
    if (!login) return;
    const previous = document.activeElement as HTMLElement;
    const handle = (e: KeyboardEvent) => {
      if (e.key === "Escape") setLogin(false);
      if (e.key === "Tab") {
        const items = modalRef.current?.querySelectorAll<HTMLElement>(
          "button:not([disabled]),input,select",
        );
        if (!items?.length) return;
        const first = items[0],
          last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener("keydown", handle);
    return () => {
      document.removeEventListener("keydown", handle);
      previous?.focus();
    };
  }, [login]);
  async function boot() {
    setError("");
    try {
      const b = await api<Bootstrap>("/bootstrap");
      setCsrf(b.csrfToken);
      setBootstrap(b);
      setDate(
        (d) => d || new URLSearchParams(location.search).get("date") || b.today,
      );
      const requested = new URLSearchParams(location.search).get("mensaId");
      if (requested && b.mensas.some((m) => m.id === requested))
        setMensa(requested);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not load the app.");
      setLoading(false);
    }
  }
  useEffect(() => {
    void boot();
  }, []);
  async function refresh() {
    if (!date || !bootstrap) return false;
    if (contextIdentity !== activeContext.current) return false;
    const owner = generation.current;
    const read = ++reads.current;
    const current = () =>
      owner === generation.current && read === reads.current;
    try {
      const [o, result] = await Promise.all([
        api<Overview>("/overview" + query(mensaId, date)),
        bootstrap.user
          ? api<{ plan: Plan | null }>("/plans?date=" + date)
          : Promise.resolve({ plan: null }),
      ]);
      if (!current()) return false;
      const p = result.plan;
      setOverview(o);
      setPlan(p);
      setSelected((selected) =>
        selected && o.slots.some((s) => s.id === selected && s.selectable)
          ? selected
          : p && p.status !== "cancelled" && p.mensaId === mensaId
            ? p.slotId
            : null,
      );
      return true;
    } catch (e) {
      if (!current()) return false;
      throw e;
    }
  }
  useEffect(() => {
    if (!date || !bootstrap) return;
    let cancelled = false;
    const owner = generation.current;
    const read = ++reads.current;
    const current = () =>
      !cancelled && owner === generation.current && read === reads.current;
    setBusy(false);
    const contextKey = mensaId + "|" + date;
    const contextChanged = context.current !== contextKey;
    if (contextChanged) setPreserveGuestDraft(false);
    const preserved = contextChanged ? null : draft.current;
    context.current = contextKey;
    setLoading(contextChanged);
    if (contextChanged) setOverview(null);
    if (!preserved) setSelected(null);
    setError("");
    void (async () => {
      try {
        const [o, p] = await Promise.all([
          api<Overview>("/overview" + query(mensaId, date)),
          bootstrap.user
            ? api<{ plan: Plan | null }>("/plans?date=" + date)
            : Promise.resolve({ plan: null }),
        ]);
        if (!current()) return;
        setOverview(o);
        setPlan(p.plan);
        const deepSlot = deepLinkUsed.current
          ? null
          : new URLSearchParams(location.search).get("slotId");
        deepLinkUsed.current = true;
        setSelected(
          preserved && o.slots.some((s) => s.id === preserved && s.selectable)
            ? preserved
            : deepSlot && o.slots.some((s) => s.id === deepSlot && s.selectable)
              ? deepSlot
              : p.plan &&
                  p.plan.status !== "cancelled" &&
                  p.plan.mensaId === mensaId
                ? p.plan.slotId
                : null,
        );
      } catch (e) {
        if (current())
          setError(
            e instanceof Error ? e.message : "Could not load lunch times.",
          );
      } finally {
        if (current()) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [date, mensaId, bootstrap?.user?.id]);
  function changeView(next: View) {
    setView(next);
    setMobileMenu(false);
    const url = new URL(location.href);
    url.searchParams.set("view", next);
    history.replaceState(null, "", url);
    document.getElementById("main-content")?.focus();
  }
  async function signIn(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setAuthError("");
    try {
      const result = await api<{
        user: NonNullable<Bootstrap["user"]>;
        csrfToken: string;
      }>("/auth/login", "POST", { email, password });
      setCsrf(result.csrfToken);
      setBootstrap((b) => (b ? { ...b, ...result } : b));
      setPreserveGuestDraft(selected !== null);
      setLogin(false);
      setMessage(
        "Signed in. Your draft is ready to review; confirm it to save.",
      );
    } catch (e) {
      setAuthError(e instanceof Error ? e.message : "Sign-in failed.");
    } finally {
      setBusy(false);
    }
  }
  async function signOut() {
    try {
      const result = await api<{ csrfToken: string }>(
        "/auth/logout",
        "POST",
        {},
      );
      setCsrf(result.csrfToken);
      setBootstrap((b) => (b ? { ...b, user: null } : b));
      setPreserveGuestDraft(false);
      setPlan(null);
      setMessage("Signed out. Private account details are no longer shown.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not sign out.");
    }
  }
  async function save(draft: Draft) {
    if (!selected || busy) return;
    const owner = generation.current;
    const current = () => owner === generation.current;
    setBusy(true);
    setError("");
    setMessage("");
    try {
      const result = await api<{ plan: Plan }>("/plans", "PUT", {
        ...draft,
        date,
        mensaId,
        slotId: selected,
        version: plan?.version || 0,
        requestId:
          mutation.current?.key ===
          JSON.stringify({
            date,
            mensaId,
            selected,
            ...draft,
            version: plan?.version || 0,
          })
            ? mutation.current.id
            : (mutation.current = {
                key: JSON.stringify({
                  date,
                  mensaId,
                  selected,
                  ...draft,
                  version: plan?.version || 0,
                }),
                id: crypto.randomUUID(),
              }).id,
      });
      if (!current()) return;
      mutation.current = null;
      setPlan(result.plan);
      setPreserveGuestDraft(false);
      if (!(await refresh()) || !current()) return;
      setMessage("Your lunch plan is confirmed.");
      changeView("plan");
    } catch (e) {
      if (!current()) return;
      if (!(e instanceof ApiError)) {
        try {
          const recovered = await api<{ plan: Plan | null }>(
            "/plans?date=" + date,
          );
          if (!current()) return;
          if (
            recovered.plan?.slotId === selected &&
            recovered.plan.mensaId === mensaId &&
            recovered.plan.status === "confirmed" &&
            recovered.plan.visibility === draft.visibility &&
            recovered.plan.meetingPointId === draft.meetingPointId &&
            recovered.plan.reminder === draft.reminder &&
            recovered.plan.version > (plan?.version || 0)
          ) {
            setPlan(recovered.plan);
            setPreserveGuestDraft(false);
            mutation.current = null;
            setMessage(
              "Your lunch plan is confirmed. Saved data was recovered after a connection interruption.",
            );
            await refresh();
            return;
          }
        } catch {
          /* Keep the same mutation ID for an uncertain retry. */
        }
      }
      if (!current()) return;
      setError(
        e instanceof ApiError && e.status === 409
          ? `${e.message} Refresh the saved plan before editing again.`
          : e instanceof Error
            ? e.message
            : "Save status is uncertain. Refresh saved data before trying again.",
      );
    } finally {
      if (current()) setBusy(false);
    }
  }
  async function cancel() {
    if (!plan || busy) return;
    const owner = generation.current;
    const current = () => owner === generation.current;
    setBusy(true);
    setError("");
    try {
      await api("/plans", "DELETE", {
        date,
        version: plan.version,
        requestId: crypto.randomUUID(),
      });
      if (!current()) return;
      setSelected(null);
      setPreserveGuestDraft(false);
      if (!(await refresh()) || !current()) return;
      setMessage(
        "Your lunch plan was cancelled. Its reminder has been cancelled too.",
      );
    } catch (e) {
      if (!current()) return;
      setError(e instanceof Error ? e.message : "Could not cancel the plan.");
    } finally {
      if (current()) setBusy(false);
    }
  }
  if (!bootstrap)
    return (
      <main className="startup">
        <Utensils size={40} />
        <h1>MensaTogether</h1>
        {error ? (
          <>
            <p role="alert">{error}</p>
            <button className="primary" onClick={() => void boot()}>
              Try again
            </button>
          </>
        ) : (
          <p role="status">Setting the table…</p>
        )}
      </main>
    );
  const mensa =
    bootstrap.mensas.find((m) => m.id === mensaId) || bootstrap.mensas[0];
  const selectable = overview?.slots.filter((s) => s.selectable) || [];
  const total = overview?.slots.reduce((sum, s) => sum + s.count, 0) || 0;
  const selectedSlot = overview?.slots.find((s) => s.id === selected);
  const displayDate = date
    ? new Date(date + "T12:00:00").toLocaleDateString("en-GB", {
        weekday: "long",
        day: "numeric",
        month: "long",
      })
    : "";
  return (
    <div className="app-shell">
      <a className="skip-link" href="#main-content">
        Skip to content
      </a>
      <aside inert={login} className={`sidebar ${mobileMenu ? "open" : ""}`}>
        <a className="brand" href="/" aria-label="MensaTogether home">
          <span className="brand-mark">
            <Utensils size={24} />
          </span>
          <span>
            Mensa<strong>Together</strong>
          </span>
        </a>
        <p className="brand-copy">Lunch, together.</p>
        <nav aria-label="Main navigation">
          {navigation.map((n) => (
            <button
              key={n.id}
              className={view === n.id ? "active" : ""}
              aria-current={view === n.id ? "page" : undefined}
              onClick={() => changeView(n.id)}
            >
              <n.icon size={21} />
              <span>{n.label}</span>
              {n.id === "plan" && plan && plan.status === "confirmed" && (
                <span className="nav-dot" />
              )}
            </button>
          ))}
        </nav>
        <div className="sidebar-note">
          <Utensils size={27} />
          <p>
            A shared table.
            <br />A better lunch break.
          </p>
          <small>
            FH Aachen community
            <br />
            Evaluation prototype
          </small>
        </div>
        <div className="account">
          {bootstrap.user ? (
            <>
              <span className="avatar">{bootstrap.user.alias[0]}</span>
              <div>
                <strong>{bootstrap.user.alias}</strong>
                <small>Evaluation account</small>
              </div>
              <button aria-label="Sign out" onClick={() => void signOut()}>
                <LogOut size={19} />
              </button>
            </>
          ) : (
            <button className="sign-in" onClick={() => setLogin(true)}>
              Sign in <ArrowRight size={17} />
            </button>
          )}
        </div>
      </aside>
      <div inert={login} className="workspace">
        <header className="topbar">
          <span className="campus-label">
            FH Aachen <span>/</span> Your campus lunch companion
          </span>
          <span className="prototype-tag">Fictional evaluation accounts</span>
          <button
            className="mobile-menu"
            aria-label="Toggle account menu"
            onClick={() => setMobileMenu(!mobileMenu)}
          >
            {mobileMenu ? <X /> : <MenuIcon />}
          </button>
        </header>
        <main id="main-content" tabIndex={-1}>
          <div className="page-heading">
            <div>
              <h1>
                {view === "today"
                  ? "Make time for lunch."
                  : view === "times"
                    ? "Find your time."
                    : view === "friends"
                      ? "Better with your people."
                      : "Your lunch, planned."}
              </h1>
              <p>
                {view === "today"
                  ? "A good break starts with a simple plan."
                  : view === "times"
                    ? "Compare submitted plans and choose one arrival time."
                    : view === "friends"
                      ? "Connect, compare, and meet at the mensa."
                      : "Choose one time. Keep the details in your hands."}
              </p>
            </div>
            <span className="date-display">
              <CalendarDays size={18} />
              {displayDate}
            </span>
          </div>
          <div className="context-bar">
            <label>
              <MapPin size={18} />
              <span className="sr-only">Mensa</span>
              <select
                aria-label="Mensa"
                value={mensaId}
                onChange={(e) => {
                  setMensa(e.target.value);
                  setMessage("");
                }}
              >
                {bootstrap.mensas.map((m) => (
                  <option key={m.id} value={m.id}>
                    {m.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              <CalendarDays size={18} />
              <span className="sr-only">Lunch date</span>
              <input
                aria-label="Lunch date"
                type="date"
                value={date}
                onChange={(e) => {
                  setDate(e.target.value);
                  setMessage("");
                }}
                required
              />
            </label>
            <div className="service-hours">
              <Clock3 size={17} />
              <span>
                {overview
                  ? overview.serviceWindows.length
                    ? overview.serviceWindows
                        .map((w) => `${w.start}–${w.end}`)
                        .join(" / ")
                    : "No mensa service this date"
                  : "Loading service hours…"}
              </span>
            </div>
          </div>
          {error && (
            <div className="notice error" role="alert">
              <span>{error}</span>
              <button
                className="text-button"
                onClick={() =>
                  void refresh()
                    .then((applied) => {
                      if (applied) setError("");
                    })
                    .catch((e) => setError(e.message))
                }
              >
                <RefreshCw size={16} /> Refresh saved data
              </button>
            </div>
          )}
          {message && (
            <div className="notice success" role="status">
              {message}
              <button
                aria-label="Dismiss message"
                onClick={() => setMessage("")}
              >
                <X size={17} />
              </button>
            </div>
          )}
          {overview?.notice && <div className="notice">{overview.notice}</div>}
          {view === "friends" ? (
            bootstrap.user ? (
              <Friends
                key={bootstrap.user.id}
                date={date}
                mensas={bootstrap.mensas}
                onChanged={() => void refresh()}
              />
            ) : (
              <section className="panel guest-friends">
                <Users size={38} />
                <h2>Your lunch circle is one sign-in away.</h2>
                <p>
                  Use a fictional evaluation account to connect with friends.
                  Plans stay private until you choose to share them.
                </p>
                <button className="primary" onClick={() => setLogin(true)}>
                  Sign in to see friends
                </button>
              </section>
            )
          ) : loading ? (
            <div className="panel loading" role="status">
              Loading lunch times and menu…
            </div>
          ) : (
            overview && (
              <>
                {view === "today" && (
                  <section className="day-summary">
                    <div>
                      <span className="summary-icon">
                        <Utensils size={25} />
                      </span>
                      <div>
                        <h2>
                          {total
                            ? `${total} lunch ${total === 1 ? "plan" : "plans"} submitted`
                            : "A fresh day at the mensa"}
                        </h2>
                        <p>
                          {selectable.length
                            ? `${selectable.length} upcoming arrival times to choose from`
                            : "No upcoming arrival slots for this date"}
                        </p>
                      </div>
                    </div>
                    <button
                      className="text-button"
                      onClick={() => changeView("times")}
                    >
                      Compare all times <ArrowRight size={17} />
                    </button>
                  </section>
                )}
                <div className="planner-layout">
                  <Times
                    slots={overview.slots}
                    selected={selected}
                    onSelect={setSelected}
                    compact={view === "today"}
                  />
                  <PlanPanel
                    plan={plan}
                    selected={selected}
                    mensa={mensa}
                    date={date}
                    slot={selectedSlot}
                    onSave={(draft) => void save(draft)}
                    onCancel={() => void cancel()}
                    busy={busy}
                    authenticated={!!bootstrap.user}
                    preserveDraft={preserveGuestDraft}
                    onLogin={() => setLogin(true)}
                  />
                </div>
                {view !== "plan" && (
                  <Menu dishes={overview.menu} notice={overview.menuNotice} />
                )}
                <p className="hours-source">
                  Regular service hours verified {overview.hoursVerifiedAt}.
                  Date-specific closures take priority. Mensa service hours
                  differ from cafeteria hours.
                </p>
              </>
            )
          )}
          <footer>
            Made for the lunch break.{" "}
            <span>No location tracking. No table reservations.</span>
          </footer>
        </main>
      </div>
      <nav inert={login} className="bottom-nav" aria-label="Mobile navigation">
        {navigation.map((n) => (
          <button
            key={n.id}
            className={view === n.id ? "active" : ""}
            aria-current={view === n.id ? "page" : undefined}
            onClick={() => changeView(n.id)}
          >
            <n.icon size={20} />
            <span>{n.label}</span>
          </button>
        ))}
      </nav>
      {login && (
        <div className="modal-backdrop">
          <section
            ref={modalRef}
            className="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="login-title"
          >
            <button
              className="modal-close"
              aria-label="Close sign in"
              onClick={() => setLogin(false)}
            >
              <X />
            </button>
            <span className="round-icon">
              <Utensils />
            </span>
            <h2 id="login-title">Welcome to the table.</h2>
            <p>
              This prototype uses fictional evaluation accounts. No campus SSO
              or student credentials are required.
            </p>
            <form onSubmit={(e) => void signIn(e)}>
              <label className="field">
                Evaluation account
                <select
                  autoFocus
                  aria-label="Evaluation account"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    setPassword(
                      bootstrap.demoAccounts.find(
                        (a) => a.email === e.target.value,
                      )?.password || "",
                    );
                  }}
                >
                  <option value="">Choose an account</option>
                  {bootstrap.demoAccounts.map((a) => (
                    <option key={a.email} value={a.email}>
                      {a.alias} · fictional account
                    </option>
                  ))}
                </select>
              </label>
              <label className="field">
                Password
                <input
                  type="password"
                  autoComplete="current-password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </label>
              {authError && (
                <p role="alert" className="error">
                  {authError}
                </p>
              )}
              <button
                className="primary full"
                disabled={busy || !email || !password}
              >
                {busy ? "Signing in…" : "Sign in"}
              </button>
            </form>
            <p className="fine-print">
              Your selected time is a draft until you explicitly confirm it.
            </p>
          </section>
        </div>
      )}
    </div>
  );
}
