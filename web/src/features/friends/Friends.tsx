import { useEffect, useRef, useState } from "react";
import QRCode from "qrcode";
import { UserPlus, Users, QrCode } from "lucide-react";
import { api, type Friends as FriendsData, type Mensa } from "../../api";
export function Friends({
  date,
  mensas,
  onChanged,
}: {
  date: string;
  mensas: Mensa[];
  onChanged: () => void;
}) {
  const [data, setData] = useState<FriendsData | null>(null);
  const [search, setSearch] = useState("");
  const [code, setCode] = useState(
    new URLSearchParams(location.search).get("invite") || "",
  );
  const [target, setTarget] = useState("");
  const [verifiedCode, setVerifiedCode] = useState("");
  const codeRevision = useRef(0);
  const [invite, setInvite] = useState<{
    code: string;
    expiresAt: string;
    url: string;
  } | null>(null);
  const [qr, setQr] = useState("");
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const camera = useRef<HTMLVideoElement>(null);
  const [scanning, setScanning] = useState(false);
  const stream = useRef<MediaStream | null>(null);
  const cameraGeneration = useRef(0);
  const activeDate = useRef(date);
  activeDate.current = date;
  const loadGeneration = useRef(0);
  const load = async () => {
    if (activeDate.current !== date) return;
    const requestedDate = date;
    const generation = ++loadGeneration.current;
    try {
      const result = await api<FriendsData>("/friends?date=" + requestedDate);
      if (
        activeDate.current === requestedDate &&
        generation === loadGeneration.current
      )
        setData(result);
    } catch (e) {
      if (
        activeDate.current === requestedDate &&
        generation === loadGeneration.current
      )
        setError(e instanceof Error ? e.message : "Could not load friends.");
    }
  };
  useEffect(() => {
    setData(null);
    setError("");
    void load();
    return () => {
      loadGeneration.current++;
    };
  }, [date]);
  useEffect(
    () => () => {
      cameraGeneration.current++;
      stream.current?.getTracks().forEach((t) => t.stop());
    },
    [],
  );
  async function action(fn: () => Promise<unknown>, success: string) {
    setBusy(true);
    setError("");
    try {
      await fn();
      setMessage(success);
      await load();
      onChanged();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Request failed.");
    } finally {
      setBusy(false);
    }
  }
  async function create() {
    await action(async () => {
      const result = await api<{
        code: string;
        expiresAt: string;
        url: string;
      }>("/invites", "POST", {});
      setInvite(result);
      setQr(
        await QRCode.toDataURL(
          result.url ? new URL(result.url, location.origin).href : result.code,
          {
            width: 200,
            margin: 2,
            color: { dark: "#142D4E", light: "#FFFFFF" },
          },
        ),
      );
    }, "Your invitation is ready. Share it only with the person you want to add.");
  }
  async function preview() {
    setTarget("");
    setVerifiedCode("");
    const revision = ++codeRevision.current;
    const requestedCode = code.trim();
    await action(async () => {
      const result = await api<{ alias: string }>(
        "/invites?code=" + encodeURIComponent(requestedCode),
      );
      if (revision !== codeRevision.current) return;
      setTarget(result.alias);
      setVerifiedCode(requestedCode);
    }, "Check the alias before sending your request.");
  }
  async function scan() {
    setError("");
    const generation = ++cameraGeneration.current;
    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        setError(
          "Camera scanning is unavailable. Enter the invitation code below.",
        );
        return;
      }
      const Detector = (
        window as unknown as {
          BarcodeDetector?: new (options: { formats: string[] }) => {
            detect: (
              video: HTMLVideoElement,
            ) => Promise<{ rawValue: string }[]>;
          };
        }
      ).BarcodeDetector;
      if (!Detector) {
        setError(
          "QR scanning is not supported by this browser. Enter the invitation code below.",
        );
        return;
      }
      setScanning(true);
      const media = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" },
      });
      if (generation !== cameraGeneration.current) {
        media.getTracks().forEach((track) => track.stop());
        return;
      }
      stream.current = media;
      await new Promise((r) => setTimeout(r, 100));
      if (camera.current && generation === cameraGeneration.current) {
        camera.current.srcObject = media;
        await camera.current.play();
        const detector = new Detector({ formats: ["qr_code"] });
        const poll = async () => {
          if (
            !stream.current ||
            !camera.current ||
            generation !== cameraGeneration.current
          )
            return;
          const hits = await detector.detect(camera.current);
          if (generation !== cameraGeneration.current) return;
          if (hits.length) {
            let value = hits[0].rawValue;
            try {
              value = new URL(value).searchParams.get("invite") || value;
            } catch {}
            setCode(value);
            codeRevision.current++;
            setTarget("");
            setVerifiedCode("");
            media.getTracks().forEach((t) => t.stop());
            stream.current = null;
            setScanning(false);
            setMessage("Code scanned. Preview the alias before sending.");
          } else setTimeout(() => void poll(), 500);
        };
        void poll();
      } else {
        media.getTracks().forEach((track) => track.stop());
        stream.current = null;
      }
    } catch {
      if (generation !== cameraGeneration.current) return;
      stream.current?.getTracks().forEach((t) => t.stop());
      setScanning(false);
      setError(
        "Camera access was not available. You can enter the invitation code instead.",
      );
    }
  }
  return (
    <div className="friends-layout">
      <section className="panel">
        <div className="section-heading">
          <div>
            <h2>Your lunch circle</h2>
            <p>Shared plans from accepted friends.</p>
          </div>
          <Users size={24} />
        </div>
        <label className="field">
          Search friends
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Find a friend by alias"
          />
        </label>
        {!data ? (
          <p role="status">Loading friends…</p>
        ) : (
          <>
            <ul className="friend-list">
              {data.friends
                .filter((f) =>
                  f.alias.toLowerCase().includes(search.toLowerCase()),
                )
                .map((f) => (
                  <li key={f.id}>
                    <span className="avatar">{f.alias.slice(0, 1)}</span>
                    <div>
                      <strong>{f.alias}</strong>
                      <p>
                        {f.sharedPlan
                          ? `${f.sharedPlan.slotId} · ${mensas.find((m) => m.id === f.sharedPlan!.mensaId)?.name || f.sharedPlan.mensaId}`
                          : "No shared details"}
                      </p>
                      {f.sharedPlan && <small>Plan confirmed</small>}
                    </div>
                    <button
                      className="text-button"
                      disabled={busy}
                      onClick={() =>
                        void action(
                          () => api("/friends", "DELETE", { id: f.id }),
                          "Friend removed. Shared details are no longer available.",
                        )
                      }
                    >
                      Remove
                    </button>
                  </li>
                ))}
            </ul>
            {data.friends.length === 0 && (
              <div className="empty">
                Your lunch circle starts here. Share a code to connect with a
                friend.
              </div>
            )}
            {data.requests.length > 0 && (
              <>
                <h3>Friend requests</h3>
                {data.requests.map((r) => (
                  <div className="request-row" key={r.id}>
                    <strong>{r.alias}</strong>
                    <button
                      className="secondary"
                      disabled={busy}
                      onClick={() =>
                        void action(
                          () => api("/friends/accept", "POST", { id: r.id }),
                          "Friend request accepted.",
                        )
                      }
                    >
                      Accept request
                    </button>
                  </div>
                ))}
              </>
            )}
            {data.outgoing.length > 0 && (
              <>
                <h3>Waiting for acceptance</h3>
                {data.outgoing.map((r) => (
                  <p key={r.id}>{r.alias} · Request sent</p>
                ))}
              </>
            )}
          </>
        )}
      </section>
      <section className="panel">
        <div className="section-heading">
          <div>
            <h2>Add a friend</h2>
            <p>A code connects you. Sharing stays your choice.</p>
          </div>
          <UserPlus size={23} />
        </div>
        <button
          className="secondary full"
          disabled={busy}
          onClick={() => void create()}
        >
          <QrCode size={18} /> Create my invite
        </button>
        {invite && (
          <div className="invite-box">
            {qr && (
              <img
                src={qr}
                alt="Your single-use friendship invitation QR code"
                width="200"
                height="200"
              />
            )}
            <code>{invite.code}</code>
            <small>
              Expires {new Date(invite.expiresAt).toLocaleString("en-GB")}
            </small>
            <button
              className="text-button"
              onClick={() =>
                void action(async () => {
                  await api("/invites", "DELETE", { code: invite.code });
                  setInvite(null);
                }, "Invitation revoked.")
              }
            >
              Revoke invitation
            </button>
          </div>
        )}
        <hr />
        <button
          className="text-button"
          disabled={scanning}
          onClick={() => void scan()}
        >
          Scan a friend’s QR code
        </button>
        {scanning && (
          <div>
            <video ref={camera} muted playsInline className="camera" />
            <button
              className="text-button"
              onClick={() => {
                cameraGeneration.current++;
                stream.current?.getTracks().forEach((t) => t.stop());
                stream.current = null;
                setScanning(false);
              }}
            >
              Stop camera
            </button>
          </div>
        )}
        <label className="field">
          Or enter an invitation code
          <input
            value={code}
            onChange={(e) => {
              codeRevision.current++;
              setCode(e.target.value);
              setTarget("");
              setVerifiedCode("");
            }}
            autoComplete="off"
            placeholder="Paste your friend’s code"
          />
        </label>
        <button
          className="secondary full"
          disabled={busy || !code.trim()}
          onClick={() => void preview()}
        >
          Preview friend
        </button>
        {target && (
          <div className="target-confirm">
            <p>
              Send a friend request to <strong>{target}</strong>?
            </p>
            <button
              className="primary full"
              disabled={busy}
              onClick={() =>
                void action(async () => {
                  await api("/friends", "POST", { code: verifiedCode });
                  setTarget("");
                  setCode("");
                }, "Request sent. Your friend must accept before details are shared.")
              }
            >
              Send friend request
            </button>
          </div>
        )}
        {error && (
          <p role="alert" className="error">
            {error}
          </p>
        )}
        {message && (
          <p role="status" className="success">
            {message}
          </p>
        )}
        <p className="fine-print">
          An accepted request never changes either person’s plan visibility.
        </p>
      </section>
    </div>
  );
}
