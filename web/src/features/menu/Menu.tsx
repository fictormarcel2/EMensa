import { useState } from "react";
import type { Dish } from "../../api";
function price(value: number | string) {
  const n = Number(value);
  return Number.isFinite(n)
    ? new Intl.NumberFormat("en-IE", {
        style: "currency",
        currency: "EUR",
      }).format(n)
    : String(value);
}
export function Menu({ dishes, notice }: { dishes: Dish[]; notice: string }) {
  const [filter, setFilter] = useState("all");
  const shown = dishes.filter(
    (d) => filter === "all" || `${d.diet}`.toLowerCase().includes(filter),
  );
  return (
    <section className="panel menu-panel" id="menu">
      <div className="section-heading">
        <div>
          <h2>On the menu</h2>
          <p>Something to look forward to.</p>
        </div>
        <label className="filter-label">
          Diet
          <select value={filter} onChange={(e) => setFilter(e.target.value)}>
            <option value="all">All dishes</option>
            <option value="vegan">Vegan</option>
            <option value="vegetarian">Vegetarian</option>
          </select>
        </label>
      </div>
      <p className="sample-note">
        {notice ||
          "Sample menu. Prices and dishes are evaluation data, not today’s operator menu."}
      </p>
      <div className="menu-grid">
        {shown.map((d) => (
          <article className="dish" key={d.id}>
            {d.image && (
              <img
                src={d.image}
                alt=""
                loading="lazy"
                onError={(e) => {
                  e.currentTarget.style.display = "none";
                }}
              />
            )}
            <div className="dish-content">
              <span className="diet">{d.diet || "Diet not verified"}</span>
              <h3>{d.name}</h3>
              <p>{d.description}</p>
              <div className="prices">
                <strong>
                  {price(d.priceInternal)} <small>internal</small>
                </strong>
                <span>{price(d.priceExternal)} external</span>
              </div>
              <small>
                Allergens:{" "}
                {d.allergens?.length
                  ? d.allergens.join(", ")
                  : "not verified; check with the operator"}
              </small>
            </div>
          </article>
        ))}
      </div>
      {!shown.length && (
        <p className="empty">No sample dishes match this filter.</p>
      )}
    </section>
  );
}
