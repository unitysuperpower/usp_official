import React, { useEffect, useState } from "react";
import { Field } from "./ui";
const marks = { bank_transfer: "BK", jazzcash: "JC", easypaisa: "EP" };
export function PaymentMethodCards({ methods, selected, onSelect }) {
    return (
        <div
            className="payment-method-grid"
            role="group"
            aria-label="Choose a payment method"
        >
            {Object.entries(methods).map(([key, item]) => (
                <button
                    type="button"
                    key={key}
                    className={
                        "payment-method-card method-" +
                        key +
                        (selected === key ? " is-selected" : "")
                    }
                    aria-pressed={selected === key}
                    onClick={() => onSelect(key)}
                >
                    <span className="payment-brand" aria-hidden="true">
                        {marks[key] || "PAY"}
                    </span>
                    <span className="payment-method-copy">
                        <strong>{item.label}</strong>
                        <small>
                            {item.qr_path
                                ? "Scan QR or transfer manually"
                                : "Transfer using account details"}
                        </small>
                    </span>
                    <span className="payment-selection" aria-hidden="true">
                        {selected === key ? "✓" : "○"}
                    </span>
                </button>
            ))}
        </div>
    );
}
export function PaymentInstructions({ method }) {
    if (!method) return null;
    return (
        <div
            className={
                "payment-instruction-card" + (method.qr_path ? " has-qr" : "")
            }
        >
            <div>
                <span className="eyebrow">TRANSFER TO THIS ACCOUNT</span>
                <h3>{method.label}</h3>
                <p className="preserve">{method.instructions}</p>
                <p className="payment-help">
                    Confirm the recipient name and amount in your banking or
                    wallet app before sending.
                </p>
            </div>
            {method.qr_path && (
                <figure className="payment-qr">
                    <img
                        src={"/storage/" + method.qr_path}
                        alt={method.label + " payment QR code"}
                        width="260"
                        height="260"
                    />
                    <figcaption>
                        <strong>Scan to pay</strong>
                        <span>Use your banking or wallet app.</span>
                        <a
                            href={"/storage/" + method.qr_path}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Open full-size QR ↗
                        </a>
                    </figcaption>
                </figure>
            )}
        </div>
    );
}
export function PaymentMethodEditor({ methodKey, method }) {
    const [file, setFile] = useState(null),
        [preview, setPreview] = useState(null),
        [remove, setRemove] = useState(false);
    useEffect(() => {
        if (!file) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    const src =
        preview ||
        (!remove && method.qr_path ? "/storage/" + method.qr_path : null);
    return (
        <fieldset className={"payment-method-editor method-" + methodKey}>
            <legend>
                <span className="payment-brand" aria-hidden="true">
                    {marks[methodKey]}
                </span>
                {method.label}
            </legend>
            <Field
                name={methodKey}
                title="Receiving account & instructions"
                type="textarea"
                value={method.instructions}
                maxLength={5000}
                placeholder="Account title, number or IBAN, bank name, and payment instructions"
            />
            <Field
                name={methodKey + "_qr"}
                title="Payment QR image (optional)"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
            />
            <small>
                Upload the QR supplied by your bank or wallet. PNG, JPG or WebP,
                up to 4 MB and 4096 × 4096 pixels. Keep its white border visible
                and test it before sharing.
            </small>
            {src && (
                <div className="payment-qr-preview">
                    <img src={src} alt={method.label + " QR preview"} />
                </div>
            )}
            {method.qr_path && (
                <Field
                    name={methodKey + "_remove_qr"}
                    title="Remove saved QR image"
                    type="checkbox"
                    value={false}
                    onChange={(e) => setRemove(e.target.checked)}
                />
            )}
        </fieldset>
    );
}
