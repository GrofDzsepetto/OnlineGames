import { useEffect, useState } from "react";
import { saveQuizResult } from "../../services/quizService";
import { useAuth } from "../../auth/AuthContext";

const QuizResult = ({
  score,
  total,
  quizSlug,
}: {
  score: number;
  total: number;
  quizSlug: string;
}) => {
  const [saveStatus, setSaveStatus] = useState<
    "idle" | "saving" | "success" | "error"
  >("idle");
  const {user} = useAuth();
  useEffect(() => {
    if(!user) return;
    if (saveStatus !== "idle") return;
    setSaveStatus("saving");
    console.log("SAVE PAYLOAD:", {
      quizSlug,
      score,
      total
    });

    saveQuizResult({
      quiz_slug: quizSlug,
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
  }, [quizSlug, score, total, saveStatus]);

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
