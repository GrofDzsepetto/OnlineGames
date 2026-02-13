import { useEffect, useState } from "react";
import { saveQuizResult } from "../../services/quizService";

const QuizResult = ({
  score,
  total,
  quizId,
}: {
  score: number;
  total: number;
  quizId: string;
}) => {
  const [saveStatus, setSaveStatus] = useState<
    "idle" | "saving" | "success" | "error"
  >("idle");

  useEffect(() => {
    if (saveStatus !== "idle") return; // 🔒 csak egyszer fusson

    setSaveStatus("saving");

    saveQuizResult({
      quiz_id: quizId,
      score,
      max_score: total,
    })
      .then(() => {
        setSaveStatus("success");
      })
      .catch((err) => {
        console.error("Save result failed:", err);
        setSaveStatus("error");
      });
  }, [quizId, score, total, saveStatus]);

  return (
    <div>
      <h2>
        Result: {score} / {total}
      </h2>

      {saveStatus === "saving" && <p>Saving result...</p>}
      {saveStatus === "success" && <p style={{ color: "green" }}>Result saved successfully ✓</p>}
      {saveStatus === "error" && <p style={{ color: "red" }}>Failed to save result</p>}
    </div>
  );
};

export default QuizResult;
