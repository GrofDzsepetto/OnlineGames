import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import type { QuizQuestion, MatchingPair } from "../../types/quiz";
import { getQuizForEdit, updateQuiz } from "../../services/quizService";

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

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }

    setLoading(true);

    getQuizForEdit(id)
      .then((q) => {
        setQuizId(q.quiz_id);
        setTitle(q.title);
        setDescription(q.description ?? "");

        const mapped: CreateQuestion[] = (q.questions ?? []).map((qq) => ({
          type: qq.type,
          question: qq.question,
          answers: qq.type === "MULTIPLE_CHOICE" ? (qq.answers ?? []) : [{ text: "", correct: false }, { text: "", correct: false }],
          pairs: qq.type === "MATCHING" ? (qq.pairs ?? []) : [{ left: "", rights: [""] }],
        }));

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

    if (!title.trim()) {
      alert("Kérlek, adj meg egy címet a kvíznek!");
      return;
    }

    const payload = {
      quiz_id: quizId,
      title: title.trim(),
      description,
      questions: questions.map((q) => ({
        text: (q.question ?? "").trim(),
        type: q.type,
        answers:
          q.type === "MULTIPLE_CHOICE"
            ? q.answers.map((a) => ({ text: a.text, isCorrect: a.correct }))
            : [],
        pairs: q.type === "MATCHING" ? q.pairs : [],
      })),
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
                            nextPairs[pIdx] = { ...nextPairs[pIdx], rights: nextRights.length ? nextRights : [""] };
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
                        nextPairs[pIdx] = { ...nextPairs[pIdx], rights: [...nextPairs[pIdx].rights, ""] };
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
