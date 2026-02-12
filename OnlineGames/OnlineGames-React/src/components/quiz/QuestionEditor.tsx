import type { MatchingPair, QuizQuestion } from "../../types/quiz";
import "../../styles/Quiz/components/questionEditor.css";


type QuestionType = "MULTIPLE_CHOICE" | "MATCHING";

export type EditableQuestion = Omit<QuizQuestion, "id"> & {
  answers: { text: string; correct: boolean }[];
  pairs: MatchingPair[];
};

type Props = {
  questions: EditableQuestion[];
  setQuestions: React.Dispatch<React.SetStateAction<EditableQuestion[]>>;
};

const QuestionEditor = ({ questions, setQuestions }: Props) => {
  const addQuestion = () => {
    setQuestions((prev) => [
      ...prev,
      {
        type: "MULTIPLE_CHOICE",
        question: "",
        answers: [
          { text: "", correct: false },
          { text: "", correct: false },
        ],
        pairs: [{ left: "", rights: [""] }],
      },
    ]);
  };

  const removeQuestion = (qIdx: number) => {
    setQuestions((prev) => prev.filter((_, i) => i !== qIdx));
  };

  const handleField = (
    qIdx: number,
    field: keyof EditableQuestion,
    value: any
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
          pairs: curr.pairs?.length
            ? curr.pairs
            : [{ left: "", rights: [""] }],
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

  return (
    <>
      {questions.map((q, qIdx) => (
        <div key={qIdx} className="cq-card">
          <div className="cq-row cq-row--header">
            <span className="cq-qnum">{qIdx + 1}.</span>

            <input
              className="cq-input"
              placeholder="Kérdés szövege"
              value={q.question}
              onChange={(e) =>
                handleField(qIdx, "question", e.target.value)
              }
            />

            <select
              className="cq-select"
              value={q.type}
              onChange={(e) =>
                setType(qIdx, e.target.value as QuestionType)
              }
            >
              <option value="MULTIPLE_CHOICE">Feleletválasztós</option>
              <option value="MATCHING">Párosítás</option>
            </select>

            <button
              className="cq-btn cq-btn--danger"
              onClick={() => removeQuestion(qIdx)}
            >
              X
            </button>
          </div>

          {/* ================= MULTIPLE CHOICE ================= */}
          {q.type === "MULTIPLE_CHOICE" && (
            <div className="cq-body cq-body--indent">
              {q.answers.map((ans, aIdx) => (
                <div key={aIdx} className="cq-row cq-row--answer">
                  <input
                    type="checkbox"
                    checked={ans.correct}
                    onChange={(e) => {
                      const next = [...q.answers];
                      next[aIdx] = {
                        ...next[aIdx],
                        correct: e.target.checked,
                      };
                      handleField(qIdx, "answers", next);
                    }}
                  />

                  <input
                    className="cq-input cq-input--answer"
                    placeholder={`Válasz ${aIdx + 1}`}
                    value={ans.text}
                    onChange={(e) => {
                      const next = [...q.answers];
                      next[aIdx] = {
                        ...next[aIdx],
                        text: e.target.value,
                      };
                      handleField(qIdx, "answers", next);
                    }}
                  />

                  <button
                    className="cq-iconbtn"
                    onClick={() => {
                      if (q.answers.length <= 2) return;
                      const next = q.answers.filter((_, i) => i !== aIdx);
                      handleField(qIdx, "answers", next);
                    }}
                  >
                    🗑️
                  </button>
                </div>
              ))}

              <button
                className="cq-btn cq-btn--secondary"
                onClick={() =>
                  handleField(qIdx, "answers", [
                    ...q.answers,
                    { text: "", correct: false },
                  ])
                }
              >
                + Válasz hozzáadása
              </button>
            </div>
          )}

          {/* ================= MATCHING ================= */}
          {q.type === "MATCHING" && (
            <div className="cq-body cq-body--matching">
              {q.pairs.map((pair, pIdx) => (
                <div key={pIdx} className="cq-pair">
                  <div className="cq-row">
                    <input
                      className="cq-input"
                      placeholder="Bal oldal"
                      value={pair.left}
                      onChange={(e) => {
                        const nextPairs = [...q.pairs];
                        nextPairs[pIdx] = {
                          ...nextPairs[pIdx],
                          left: e.target.value,
                        };
                        handleField(qIdx, "pairs", nextPairs);
                      }}
                    />

                    <button
                      className="cq-btn cq-btn--outlineDanger"
                      onClick={() => {
                        const nextPairs = q.pairs.filter(
                          (_, i) => i !== pIdx
                        );
                        handleField(
                          qIdx,
                          "pairs",
                          nextPairs.length
                            ? nextPairs
                            : [{ left: "", rights: [""] }]
                        );
                      }}
                    >
                      Törlés
                    </button>
                  </div>

                  {pair.rights.map((r, rIdx) => (
                    <div key={rIdx} className="cq-row">
                      <input
                        className="cq-input"
                        placeholder="Jobb oldal"
                        value={r}
                        onChange={(e) => {
                          const nextPairs = [...q.pairs];
                          const nextRights = [...nextPairs[pIdx].rights];
                          nextRights[rIdx] = e.target.value;

                          nextPairs[pIdx] = {
                            ...nextPairs[pIdx],
                            rights: nextRights,
                          };

                          handleField(qIdx, "pairs", nextPairs);
                        }}
                      />

                      <button
                        className="cq-iconbtn"
                        onClick={() => {
                          const nextPairs = [...q.pairs];
                          const nextRights =
                            nextPairs[pIdx].rights.filter(
                              (_, i) => i !== rIdx
                            );

                          nextPairs[pIdx] = {
                            ...nextPairs[pIdx],
                            rights: nextRights.length
                              ? nextRights
                              : [""],
                          };

                          handleField(qIdx, "pairs", nextPairs);
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
                      handleField(qIdx, "pairs", nextPairs);
                    }}
                  >
                    + Jobb elem
                  </button>
                </div>
              ))}

              <button
                className="cq-btn cq-btn--gray"
                onClick={() =>
                  handleField(qIdx, "pairs", [
                    ...q.pairs,
                    { left: "", rights: [""] },
                  ])
                }
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
      </div>
    </>
  );
};

export default QuestionEditor;
