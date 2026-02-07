import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import type { QuizQuestion, MatchingPair } from "../types/quiz";
import { getQuizForEdit, updateQuiz } from "../services/quizService";
import "../styles/createQuiz.css";

type QuestionType = "MULTIPLE_CHOICE" | "MATCHING";

type CreateQuestion = Omit<QuizQuestion, "id"> & {
  answers: { text: string; correct: boolean }[];
  pairs: MatchingPair[];
};

const EditQuiz = () => {
  const navigate = useNavigate();
  const { id } = useParams();

  const [loading, setLoading] = useState(true);
  const [quizId, setQuizId] = useState<string>("");

  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [questions, setQuestions] = useState<CreateQuestion[]>([]);

  // ✅ Láthatóság
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
        // ✅ get_quiz_for_edit.php formátum:
        // { quiz_id, title, description, isPublic, viewerEmails, questions[] }
        console.log("EDIT QUIZ RESPONSE:", q);
        setQuizId(q.quiz_id ?? "");
        setTitle(q.title ?? "");
        setDescription(q.description ?? "");

        const pub = !!q.isPublic;
        setIsPublic(pub);

        const emails = Array.isArray(q.viewerEmails) ? q.viewerEmails : [];
        setViewerEmailsText(!pub ? emails.join("\n") : "");

        const mapped: CreateQuestion[] = (q.questions ?? []).map((qq: any) => {
          const type: QuestionType = (qq.type ?? "MULTIPLE_CHOICE") as QuestionType;

          if (type === "MULTIPLE_CHOICE") {
            const answers = Array.isArray(qq.answers) ? qq.answers : [];
            return {
              type,
              question: qq.question ?? "",
              answers:
                answers.length > 0
                  ? answers.map((a: any) => ({
                      text: a.text ?? "",
                      correct: !!a.isCorrect,
                    }))
                  : [
                      { text: "", correct: false },
                      { text: "", correct: false },
                    ],
              pairs: [{ left: "", rights: [""] }],
            };
          }

          // MATCHING
          const pairs = Array.isArray(qq.pairs) ? qq.pairs : [];
          return {
            type,
            question: qq.question ?? "",
            answers: [
              { text: "", correct: false },
              { text: "", correct: false },
            ],
            pairs: pairs.length ? pairs : [{ left: "", rights: [""] }],
          };
        });

        setQuestions(mapped.length ? mapped : []);
      })
      .catch((e: any) => {
        alert(e?.message ?? "Nem sikerült betölteni a kvízt");
      })
      .finally(() => setLoading(false));
  }, [id]);

  const addQuestion = () => {
    const newQuestion: CreateQuestion = {
      type: "MULTIPLE_CHOICE",
      question: "",
      answers: [
        { text: "", correct: false },
        { text: "", correct: false },
      ],
      pairs: [{ left: "", rights: [""] }],
    };

    setQuestions((prev) => [...prev, newQuestion]);
  };

  const removeQuestion = (qIdx: number) => {
    setQuestions((prev) => prev.filter((_, i) => i !== qIdx));
  };

  const handleQuestionField = <K extends keyof CreateQuestion>(
    qIdx: number,
    field: K,
    value: CreateQuestion[K]
  ) => {
    setQuestions((prev) => {
      const updated = [...prev];
      updated[qIdx] = { ...updated[qIdx], [field]: value };
      return updated;
    });
  };

  const setType = (qIdx: number, newType: QuestionType) => {
    setQuestions((prev) => {
      const updated = [...prev];
      const curr = updated[qIdx];

      if (newType === "MATCHING") {
        updated[qIdx] = {
          ...curr,
          type: "MATCHING",
          pairs: curr.pairs?.length ? curr.pairs : [{ left: "", rights: [""] }],
        };
      } else {
        updated[qIdx] = {
          ...curr,
          type: "MULTIPLE_CHOICE",
          answers: curr.answers?.length
            ? curr.answers
            : [
                { text: "", correct: false },
                { text: "", correct: false },
              ],
        };
      }

      return updated;
    });
  };

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

    // ✅ Private esetén legyen legalább 1 email
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
      questions: cleanQuestions,

      // ✅ Láthatóság mezők az update_quiz.php-hoz
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

        {/* ✅ Láthatóság */}
        <div className="cq-row" style={{ marginTop: 12, gap: 12, alignItems: "center" }}>
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
              {isPublic ? "Public (mindenki láthatja)" : "Private (csak megadott emailek)"}
            </span>
          </label>
        </div>

        {/* ✅ Csak Private esetén */}
        {!isPublic && (
          <div style={{ marginTop: 12 }}>
            <div style={{ fontWeight: 600, marginBottom: 6 }}>Ki láthatja? (email lista)</div>
            <textarea
              className="cq-textarea"
              placeholder={
                "Írj be emaileket (soronként vagy vesszővel elválasztva)\npl:\nvalaki@gmail.com\nmasik@outlook.com"
              }
              value={viewerEmailsText}
              onChange={(e) => setViewerEmailsText(e.target.value)}
            />
            <div style={{ marginTop: 6, fontSize: 13, opacity: 0.8 }}>
              Felismert emailek: {parsedViewerEmails.length ? parsedViewerEmails.join(", ") : "—"}
            </div>
          </div>
        )}
      </section>

      {questions.map((q, qIdx) => (
        <div key={qIdx} className="cq-card">
          <div className="cq-row cq-row--header">
            <span className="cq-qnum">{qIdx + 1}.</span>

            <input
              className="cq-input"
              placeholder="Kérdés szövege"
              value={q.question}
              onChange={(e) => handleQuestionField(qIdx, "question", e.target.value)}
            />

            <select
              className="cq-select"
              value={q.type}
              onChange={(e) => setType(qIdx, e.target.value as QuestionType)}
            >
              <option value="MULTIPLE_CHOICE">Feleletválasztós</option>
              <option value="MATCHING">Párosítás</option>
            </select>

            <button className="cq-btn cq-btn--danger" onClick={() => removeQuestion(qIdx)}>
              X
            </button>
          </div>

          {q.type === "MULTIPLE_CHOICE" ? (
            <div className="cq-body cq-body--indent">
              {q.answers.map((ans, aIdx) => (
                <div key={aIdx} className="cq-row cq-row--answer">
                  <input
                    type="checkbox"
                    checked={ans.correct}
                    onChange={(e) => {
                      const next = [...q.answers];
                      next[aIdx] = { ...next[aIdx], correct: e.target.checked };
                      handleQuestionField(qIdx, "answers", next);
                    }}
                  />

                  <input
                    className="cq-input cq-input--answer"
                    placeholder={`Válasz ${aIdx + 1}`}
                    value={ans.text}
                    onChange={(e) => {
                      const next = [...q.answers];
                      next[aIdx] = { ...next[aIdx], text: e.target.value };
                      handleQuestionField(qIdx, "answers", next);
                    }}
                  />

                  <button
                    className="cq-iconbtn"
                    title={q.answers.length <= 2 ? "Minimum 2 válasz kell" : "Törlés"}
                    onClick={() => {
                      if (q.answers.length <= 2) return;
                      const next = q.answers.filter((_, i) => i !== aIdx);
                      handleQuestionField(qIdx, "answers", next);
                    }}
                  >
                    🗑️
                  </button>
                </div>
              ))}

              <button
                className="cq-btn cq-btn--secondary"
                onClick={() => {
                  const next = [...q.answers, { text: "", correct: false }];
                  handleQuestionField(qIdx, "answers", next);
                }}
              >
                + Válasz hozzáadása
              </button>
            </div>
          ) : (
            <div className="cq-body cq-body--matching">
              <h4 className="cq-subtitle">Párok (BAL → JOBB)</h4>

              {q.pairs.map((pair, pIdx) => (
                <div key={pIdx} className="cq-pair">
                  <div className="cq-row cq-row--pairTop">
                    <input
                      className="cq-input"
                      placeholder="Bal oldal (pl: kutya)"
                      value={pair.left}
                      onChange={(e) => {
                        const nextPairs = [...q.pairs];
                        nextPairs[pIdx] = { ...nextPairs[pIdx], left: e.target.value };
                        handleQuestionField(qIdx, "pairs", nextPairs);
                      }}
                    />

                    <button
                      className="cq-btn cq-btn--outlineDanger"
                      onClick={() => {
                        const nextPairs = q.pairs.filter((_, i) => i !== pIdx);
                        handleQuestionField(
                          qIdx,
                          "pairs",
                          nextPairs.length ? nextPairs : [{ left: "", rights: [""] }]
                        );
                      }}
                    >
                      Törlés
                    </button>
                  </div>

                  <div className="cq-rights">
                    <div className="cq-label">Jobb oldali elemek (1 vagy több)</div>

                    {pair.rights.map((r, rIdx) => (
                      <div key={rIdx} className="cq-row cq-row--right">
                        <input
                          className="cq-input"
                          placeholder="Jobb oldal (pl: dog)"
                          value={r}
                          onChange={(e) => {
                            const nextPairs = [...q.pairs];
                            const nextRights = [...nextPairs[pIdx].rights];
                            nextRights[rIdx] = e.target.value;
                            nextPairs[pIdx] = { ...nextPairs[pIdx], rights: nextRights };
                            handleQuestionField(qIdx, "pairs", nextPairs);
                          }}
                        />

                        <button
                          className="cq-iconbtn"
                          title="Törlés"
                          onClick={() => {
                            const nextPairs = [...q.pairs];
                            const nextRights = nextPairs[pIdx].rights.filter((_, i) => i !== rIdx);
                            nextPairs[pIdx] = {
                              ...nextPairs[pIdx],
                              rights: nextRights.length ? nextRights : [""],
                            };
                            handleQuestionField(qIdx, "pairs", nextPairs);
                          }}
                        >
                          🗑️
                        </button>
                      </div>
                    ))}

                    <button
                      className="cq-btn cq-btn--secondary"
                      onClick={() => {
                        const nextPairs = [...q.pairs];
                        nextPairs[pIdx] = {
                          ...nextPairs[pIdx],
                          rights: [...nextPairs[pIdx].rights, ""],
                        };
                        handleQuestionField(qIdx, "pairs", nextPairs);
                      }}
                    >
                      + Jobb elem
                    </button>
                  </div>
                </div>
              ))}

              <button
                className="cq-btn cq-btn--gray"
                onClick={() => {
                  const nextPairs = [...q.pairs, { left: "", rights: [""] }];
                  handleQuestionField(qIdx, "pairs", nextPairs);
                }}
              >
                + Új pár
              </button>
            </div>
          )}
        </div>
      ))}

      <div className="cq-footer">
        <button className="cq-btn cq-btn--primary" onClick={addQuestion}>
          + Új kérdés hozzáadása
        </button>

        <button className="cq-btn cq-btn--save" onClick={submit}>
          Mentés
        </button>
      </div>
    </div>
  );
};

export default EditQuiz;
