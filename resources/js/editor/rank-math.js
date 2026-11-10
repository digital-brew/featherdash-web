import { select } from "@wordpress/data";
import { addFilter } from "@wordpress/hooks";

/**
 * Rank Math analyzes the saved post content, but the `featherdash/*` sections are dynamic
 * blocks: their saved markup is an empty comment and the HTML only exists on the frontend.
 * Feed Rank Math the sections' text as HTML with the same structure as the Blade templates
 * (hero heading = h1, section headings = h2, item titles = h3) so its score reflects the page.
 * Editor-only: nothing here changes what is saved or rendered.
 */

const attr = (value) => String(value ?? "").replace(/"/g, "&quot;");
const tag = (name, value) => (value ? `<${name}>${value}</${name}>` : "");
const link = (url, text) => (text ? `<a href="${attr(url || "#")}">${text}</a>` : "");
const image = (id) => {
  const media = id ? select("core").getMedia(id) : null;
  return media?.source_url
    ? `<img src="${attr(media.source_url)}" alt="${attr(media.alt_text)}">`
    : "";
};
const list = (items, render) => (items || []).map(render).join("");

const sections = {
  hero: (a) =>
    tag("p", a.badge) +
    tag("h1", a.heading) +
    tag("p", a.text) +
    `<p>${link(a.primaryUrl, a.primaryText)} ${link(a.secondaryUrl, a.secondaryText)}</p>` +
    image(a.mediaId) +
    tag("p", a.stickerOne) +
    tag("p", a.stickerTwo) +
    tag("p", a.stickerThree),
  intro: (a) => tag("p", a.eyebrow) + tag("h2", a.heading) + tag("p", a.text),
  highlights: (a) => list(a.items, (item) => tag("h3", item.title) + tag("p", item.text)),
  video: (a) =>
    tag("p", a.eyebrow) +
    tag("h2", a.heading) +
    tag("p", a.text) +
    tag("p", a.caption) +
    list(a.chapters, (chapter) => tag("li", chapter.title)),
  features: (a) =>
    tag("h2", a.heading) +
    tag("p", a.text) +
    list(a.items, (item) => image(item.mediaId) + tag("h3", item.title) + tag("p", item.text)),
  comparison: (a) =>
    tag("h2", a.heading) +
    `<table><tr><th></th>${list(a.columns, (column) => `<th>${column}</th>`)}</tr>${list(
      a.rows,
      (row) =>
        `<tr><th>${row.label || ""}</th>${list(row.values, (value) => `<td>${value}</td>`)}</tr>`,
    )}</table>` +
    (a.chips?.length ? `<ul>${list(a.chips, (chip) => `<li>${chip}</li>`)}</ul>` : "") +
    tag("p", [a.testedLabel, (a.tested || []).join(", ")].filter(Boolean).join(": ")),
  pricing: (a) =>
    tag("h2", a.heading) +
    tag("p", a.text) +
    list(
      a.plans,
      (plan) =>
        tag("h3", plan.name) +
        tag("p", plan.sites) +
        tag("p", [plan.yearly, plan.lifetime].filter(Boolean).join(" / ")) +
        `<p>${link(plan.url, [a.ctaPrefix, plan.name].filter(Boolean).join(" "))}</p>`,
    ) +
    tag("p", a.footnote),
  story: (a) =>
    tag("h2", a.heading) +
    tag("p", a.text) +
    list(a.rows, (row) => tag("h3", row.title) + tag("p", row.text)),
  reviews: (a) =>
    tag("h2", a.heading) +
    list(
      a.items,
      (item) =>
        tag("h3", item.title) +
        tag("blockquote", item.quote) +
        tag("p", [item.name, item.role].filter(Boolean).join(", ")),
    ),
  faq: (a) =>
    tag("h2", a.heading) +
    list(a.items, (item) => tag("h3", item.question) + tag("p", item.answer)),
};

const sectionsHtml = (blocks) =>
  blocks
    .map((block) => {
      const render = sections[block.name.replace("featherdash/", "")];
      return block.name.startsWith("featherdash/") && render ? render(block.attributes) : "";
    })
    .join("\n");

addFilter("rank_math_content", "featherdash/landing-sections", (content) => {
  const blocks = select("core/block-editor").getBlocks();

  if (!blocks.some((block) => block.name.startsWith("featherdash/"))) {
    return content;
  }

  // Drop the sections' attribute comments so their JSON isn't counted as words.
  const rest = String(content).replace(/<!-- wp:featherdash\/[\s\S]*?\/-->/g, "");

  return `${sectionsHtml(blocks)}\n${rest}`;
});
