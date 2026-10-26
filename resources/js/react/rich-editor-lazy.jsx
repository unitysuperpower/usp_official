import React, { Suspense, lazy } from "react";

// The editor is loaded on demand so public pages don't download it. While it loads, a plain
// textarea with the same name keeps the form usable.
const Editor = lazy(() => import("./rich-editor"));

export default function RichEditor(props) {
    return (
        <Suspense fallback={<textarea className="rte-fallback" name={props.name} defaultValue={props.value || ""} rows={12} aria-label="Content" />}>
            <Editor {...props} />
        </Suspense>
    );
}
