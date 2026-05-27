type LanguageCode = "hu" | "en";

type Props = {
  title: string;
  setTitle: (v: string) => void;

  description: string;
  setDescription: (v: string) => void;

  language: LanguageCode;
  setLanguage: (v: LanguageCode) => void;

  isPublic: boolean;
  setIsPublic: (v: boolean) => void;

  viewerEmailsText: string;
  setViewerEmailsText: (v: string) => void;
};

const QuizMetaForm = ({
  title,
  setTitle,
  description,
  setDescription,
  language,
  setLanguage,
  isPublic,
  setIsPublic,
  viewerEmailsText,
  setViewerEmailsText,
}: Props) => {
  return (
    <section className="cq-section cq-section--meta">
      <input
        className="cq-input cq-input--title"
        placeholder="Kvíz címe"
        value={title}
        onChange={(e) => setTitle(e.target.value)}
      />

      <textarea
        className="cq-textarea"
        placeholder="Leírás"
        value={description}
        onChange={(e) => setDescription(e.target.value)}
      />

      <div style={{ marginTop: 12 }}>
        <label style={{ fontWeight: 600, marginRight: 10 }}>
          Nyelv:
        </label>
        <select
          className="cq-select"
          value={language}
          onChange={(e) => setLanguage(e.target.value as LanguageCode)}
        >
          <option value="hu">Magyar</option>
          <option value="en">English</option>
        </select>
      </div>

      <div style={{ marginTop: 12 }}>
        <label style={{ display: "flex", gap: 10, alignItems: "center" }}>
          <input
            type="checkbox"
            checked={isPublic}
            onChange={(e) => {
              const next = e.target.checked;
              setIsPublic(next);
              if (next) setViewerEmailsText("");
            }}
          />
          <span style={{ fontWeight: 600 }}>
            {isPublic
              ? "Public (mindenki láthatja)"
              : "Private (csak megadott emailek)"}
          </span>
        </label>
      </div>

      {!isPublic && (
        <div style={{ marginTop: 12 }}>
          <div style={{ fontWeight: 600, marginBottom: 6 }}>
            Ki láthatja? (email lista)
          </div>
          <textarea
            className="cq-textarea"
            value={viewerEmailsText}
            onChange={(e) => setViewerEmailsText(e.target.value)}
          />
        </div>
      )}
    </section>
  );
};

export default QuizMetaForm;
