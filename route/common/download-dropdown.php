<?php
/**
 * Shared "Download" dropdown button for table action columns.
 *
 * download_dropdown(array $items, ?string $label = null, string $kind = 'download'): string
 *   $label — button text (default: translated "Download")
 *   $kind  — 'download' or 'show': icon used on the button and, by default, on every item
 *
 * Each item is an associative array:
 *   label    (string, required) — visible text (already translated)
 *   href     (string, required) — link target
 *   modal    (bool)   — open href in a modal (data-bs-toggle="modal")
 *   target   (string) — modal target, default '#modal1'
 *   disabled (bool)   — render as disabled item
 *   muted    (bool)   — with disabled: grey instead of red (unavailable vs. not permitted)
 *   icon     (string) — 'download' or 'show'; overrides the dropdown's $kind for this item
 *   title    (string) — tooltip (e.g. reason why disabled)
 *   divider  (bool)   — render a divider instead of a link (other keys ignored)
 *
 * Uses a fixed-position popper so the menu is not clipped by .table-responsive.
 */
if (!function_exists('download_dropdown')) {
	function download_dropdown(array $items, ?string $label = null, string $kind = 'download'): string {
		if (empty($items)) { return ''; }

		$icons = [
			'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>',
			'show'     => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>',
		];
		$kind = isset($icons[$kind]) ? $kind : 'download';

		$html  = "<div class='dropdown d-inline-block me-1'>";
		$html .= "<button type='button' class='btn btn-sm bg-info-lt text-info dropdown-toggle' data-bs-toggle='dropdown' data-bs-popper-config='{\"strategy\":\"fixed\"}' aria-expanded='false'>" . $icons[$kind] . " " . ($label ?? _("Download")) . "</button>";
		$html .= "<div class='dropdown-menu dropdown-menu-end'>";
		foreach ($items as $item) {
			if (!empty($item['divider'])) {
				$html .= "<div class='dropdown-divider'></div>";
				continue;
			}
			$item_icon = $icons[$item['icon'] ?? $kind] ?? $icons[$kind];
			$label     = "<span class='me-1'>{$item_icon}</span>" . $item['label'];
			$title = isset($item['title']) ? " title='" . htmlspecialchars($item['title'], ENT_QUOTES) . "'" : "";
			if (!empty($item['disabled'])) {
				$cls   = !empty($item['muted']) ? 'text-muted' : 'text-danger';
				$html .= "<span class='dropdown-item disabled {$cls}'{$title}>{$label}</span>";
				continue;
			}
			$href  = htmlspecialchars($item['href'], ENT_QUOTES);
			$modal = !empty($item['modal'])
				? " data-bs-toggle='modal' data-bs-target='" . htmlspecialchars($item['target'] ?? '#modal1', ENT_QUOTES) . "'"
				: "";
			$html .= "<a class='dropdown-item' href='{$href}'{$modal}{$title}>{$label}</a>";
		}
		$html .= "</div></div>";
		return $html;
	}
}
