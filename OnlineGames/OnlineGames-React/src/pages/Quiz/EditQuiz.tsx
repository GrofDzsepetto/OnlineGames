import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import type { MatchingPair } from "../../types/quiz";
import { getQuizForEdit, updateQuiz } from "../../services/quizService";
import QuestionEditor from "../../components/quiz/QuestionEditor";
import type { EditableQuestion } from "../../components/quiz/QuestionEditor";
import QuizMetaForm from "../../components/quiz/QuizMetaForm";

type LanguageCode = "hu" | "en";

const EditQuiz = () => {
  const navigate = useNavigate();
  const { id } = useParams();

  const [loading, setLoading] = useState(true);
  const [quizId, setQuizId] = useState<string>("");

  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [language, setLanguage] = useState<LanguageCode>("hu");

  const [questions, setQuestions] = useState<EditableQuestion[]>([]);

  const [isPublic, setIsPublic] = useState(true);
  const [viewerEmailsText, setViewerEmailsText] = useState("");

  const parsedViewerEmails = useMemo(() => {
    const raw = viewerEmailsText
      .split(/[\n,;\s]+/g)
      .map((x) => x.trim().toLowerCase())
      .filter(Boolean);

    return Array.from(new Set(raw));
  }, [viewerEmailsText]);

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }

    setLoading(true);

    getQuizForEdit(id)
      .then((q: any) => {
        setQuizId(q.quiz_id ?? q.id ?? "");
        setTitle(q.title ?? "");
        setDescription(q.description ?? "");

        const langFromApi =
          q.language ??
          q.lang ??
          q.quiz_language ??
          "hu";

        setLanguage(langFromApi === "en" ? "en" : "hu");

        const pubFromApi =
          q.isPublic ??
          q.is_public ??
          q.public ??
          true;

        const pub = Boolean(pubFromApi);
        setIsPublic(pub);

        const emails =
          q.viewerEmails ??
          q.viewer_emails ??
          [];

        setViewerEmailsText(!pub ? emails.join("\n") : "");

        const mapped: EditableQuestion[] = (q.questions ?? []).map((qq: any) => {
          if (qq.type === "MATCHING") {
            return {
              type: "MATCHING",
              question: qq.question ?? "",
              answers: [
                { text: "", correct: false },
                { text: "", correct: false },
              ],
              pairs:
                Array.isArray(qq.pairs) && qq.pairs.length
                  ? qq.pairs
                  : [{ left: "", rights: [""] }],
            };
          }

          return {
            type: "MULTIPLE_CHOICE",
            question: qq.question ?? "",
            answers:
              Array.isArray(qq.answers) && qq.answers.length
                ? qq.answers.map((a: any) => ({
                    text: a.text ?? a.ANSWER_TEXT ?? "",
                    correct:
                      a.correct !== undefined
                        ? !!a.correct
                        : a.isCorrect !== undefined
                        ? !!a.isCorrect
                        : false,
                  }))
                : [
                    { text: "", correct: false },
                    { text: "", correct: false },
                  ],
            pairs: [{ left: "", rights: [""] }],
          };
        });

        setQuestions(mapped);
      })
      .catch((e: any) => {
        alert(e?.message ?? "Nem sikerült betölteni a kvízt");
      })
      .finally(() => setLoading(false));
  }, [id]);

  const submit = async () => {
    if (!quizId) {
      alert("Hiányzó quiz id");
      return;
    }

    const cleanTitle = title.trim();
    if (!cleanTitle) {
      alert("Kérlek, adj meg egy címet a kvíznek!");
      return;
    }

    if (!isPublic && parsedViewerEmails.length === 0) {
      alert("Privát kvíznél add meg, ki láthatja (legalább 1 email)!");
      return;
    }

    const cleanQuestions = questions
      .map((q) => {
        const text = (q.question ?? "").trim();
        if (!text) return null;

        if (q.type === "MULTIPLE_CHOICE") {
          const answers = (q.answers ?? [])
            .map((a) => ({
              text: (a.text ?? "").trim(),
              isCorrect: !!a.correct,
            }))
            .filter((a) => a.text.length > 0);

          if (answers.length < 2) return null;

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

        if (pairs.length === 0) return null;

        return {
          text,
          type: "MATCHING" as const,
          answers: [],
          pairs,
        };
      })
      .filter((x) => x !== null);

    if (cleanQuestions.length === 0) {
      alert("Adj meg legalább 1 érvényes kérdést!");
      return;
    }

    const payload = {
      quiz_id: quizId,
      title: cleanTitle,
      description: (description ?? "").trim(),
      language,
      questions: cleanQuestions,
      isPublic,
      viewerEmails: isPublic ? [] : parsedViewerEmails,
    };

    try {
      await updateQuiz(payload);
      alert("Kvíz sikeresen frissítve!");
      navigate("/quizzes");
    } catch (e: any) {
      alert(e?.message ?? "Mentés sikertelen");
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="cq-container">
      <h1 className="cq-title">Kvíz szerkesztése</h1>

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
        onClick={submit}
      >
        Kvíz Mentése
      </button>
    </div>
    </div>
  );
};

export default EditQuiz;
