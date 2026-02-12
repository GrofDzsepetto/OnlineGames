import { useState, useMemo } from "react";
import { useNavigate } from "react-router-dom";
import type { MatchingPair } from "../../types/quiz";
import { createQuiz } from "../../services/quizService";
import type { EditableQuestion } from "../../components/quiz/QuestionEditor";
import QuestionEditor from "../../components/quiz/QuestionEditor";
import QuizMetaForm from "../../components/quiz/QuizMetaForm";

type LanguageCode = "hu" | "en";

const CreateQuiz = () => {
  const navigate = useNavigate();

  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [language, setLanguage] = useState<LanguageCode>("hu");

  const [isPublic, setIsPublic] = useState(true);
  const [viewerEmailsText, setViewerEmailsText] = useState("");

  const [questions, setQuestions] = useState<EditableQuestion[]>([]);

  const parsedViewerEmails = useMemo(() => {
    const raw = viewerEmailsText
      .split(/[\n,;\s]+/g)
      .map((x) => x.trim().toLowerCase())
      .filter(Boolean);

    return Array.from(new Set(raw));
  }, [viewerEmailsText]);

  const submitQuiz = async () => {
    const cleanTitle = title.trim();
    if (!cleanTitle) {
      alert("Kérlek, adj meg egy címet a kvíznek!");
      return;
    }

    if (!isPublic && parsedViewerEmails.length === 0) {
      alert("Privát kvíznél adj meg legalább 1 email címet!");
      return;
    }

    const cleanQuestions = questions
      .map((q) => {
        const text = (q.question ?? "").trim();

        if (q.type === "MULTIPLE_CHOICE") {
          const answers = (q.answers ?? [])
            .map((a) => ({
              text: (a.text ?? "").trim(),
              isCorrect: !!a.correct,
            }))
            .filter((a) => a.text.length > 0);

          if (!text || answers.length < 2) return null;

          return {
            text,
            type: "MULTIPLE_CHOICE" as const,
            answers,
            pairs: [],
          };
        }

        const pairs = (q.pairs ?? [])
          .map((p) => {
            const left = (p.left ?? "").trim();
            const rights = (p.rights ?? [])
              .map((r) => (r ?? "").trim())
              .filter((r) => r.length > 0);

            if (!left || rights.length === 0) return null;
            return { left, rights };
          })
          .filter((x): x is MatchingPair => x !== null);

        if (!text || pairs.length === 0) return null;

        return {
          text,
          type: "MATCHING" as const,
          answers: [],
          pairs,
        };
      })
      .filter((x) => x !== null);

    if (cleanQuestions.length === 0) {
      alert("Adj hozzá legalább 1 érvényes kérdést!");
      return;
    }

    const payload = {
      title: cleanTitle,
      description: (description ?? "").trim(),
      language,
      questions: cleanQuestions,
      isPublic,
      viewerEmails: isPublic ? [] : parsedViewerEmails,
    };

    try {
      const created = await createQuiz(payload);

      if (!created?.quiz_id) {
        alert("A kvíz létrejött, de nem jött vissza quiz_id.");
        return;
      }

      alert("Kvíz sikeresen létrehozva!");
      navigate("/quizzes");
    } catch (err: any) {
      alert("Hiba történt: " + (err?.message || "Ismeretlen hiba"));
    }
  };

  return (
    <div className="cq-container">
      <h1 className="cq-title">Új Kvíz Létrehozása</h1>

      <QuizMetaForm
        title={title}
        setTitle={setTitle}
        description={description}
        setDescription={setDescription}
        language={language}
        setLanguage={setLanguage}
        isPublic={isPublic}
        setIsPublic={setIsPublic}
        viewerEmailsText={viewerEmailsText}
        setViewerEmailsText={setViewerEmailsText}
      />

      <QuestionEditor
        questions={questions}
        setQuestions={setQuestions}
      />

    <div
      className="cq-footer"
      style={{
        display: "flex",
        justifyContent: "space-between",
        marginTop: 24,
      }}
    >
      <button
        className="cq-btn"
        onClick={() => navigate("/quizzes")}
      >
        Mégse
      </button>

      <button
        className="cq-btn cq-btn--save"
        onClick={submitQuiz}
      >
        Kvíz Mentése
      </button>
    </div>

    </div>
  );
};

export default CreateQuiz;
