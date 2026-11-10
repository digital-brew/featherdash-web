/**
 * Icons shared with Blade (`<x-featherdash-icon>`), read from resources/images/icons/featherdash.
 */
const files = import.meta.glob("../../../images/icons/featherdash/*.svg", {
  query: "?raw",
  import: "default",
  eager: true,
});

export const icons = Object.fromEntries(
  Object.entries(files).map(([path, svg]) => [path.split("/").pop().replace(".svg", ""), svg]),
);

export const iconNames = Object.keys(icons);

export const Icon = ({ name, size = 28 }) =>
  icons[name] ? (
    <span
      aria-hidden="true"
      style={{ display: "block", width: size, height: size }}
      dangerouslySetInnerHTML={{
        __html: icons[name].replace("<svg ", `<svg width="${size}" height="${size}" `),
      }}
    />
  ) : null;
