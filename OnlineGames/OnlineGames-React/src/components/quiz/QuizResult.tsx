import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { saveQuizResult } from "../../services/quizService";
import { useAuth } from "../../auth/AuthContext";
import { useNavigate } from "react-router-dom";
import "../../styles/Quiz/quizResult.css";

interface Props {
  score: number;
  total: number;
  quizSlug: string;
}

const QuizResult = ({ score, total, quizSlug }: Props) => {
  const [saveStatus, setSaveStatus] = useState<
    "idle" | "saving" | "success" | "error"
  >("idle");

  const [displayScore, setDisplayScore] = useState(0);

  const { user } = useAuth();
  const navigate = useNavigate();

  // 🎯 Score count-up animáció
  useEffect(() => {
    if (score === 0) {
      setDisplayScore(0);
      return;
    }

    let start = 0;
    const duration = 1000;
    const stepTime = Math.max(Math.floor(duration / score), 20);

    const timer = setInterval(() => {
      start += 1;
      setDisplayScore(start);
      if (start >= score) clearInterval(timer);
    }, stepTime);

    return () => clearInterval(timer);
  }, [score]);

  // 💾 Save logic
  useEffect(() => {
    if (!user) return;
    if (saveStatus !== "idle") return;

    setSaveStatus("saving");

    saveQuizResult({
      quiz_slug: quizSlug,
      score,
      max_score: total,
    })
      .then(() => {
        setSaveStatus("success");
      })
      .catch(() => {
        setSaveStatus("error");
      });
  }, [quizSlug, score, total, saveStatus, user]);

  const percentage = Math.round((score / total) * 100);

  return (
    <div className="quiz-result-container">
      <motion.div
        className="quiz-result-card"
        initial={{ opacity: 0, scale: 0.8, y: 40 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        transition={{ duration: 0.5 }}
      >
        <motion.h2
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.3 }}
        >
          🎉 Quiz Finished!
        </motion.h2>

        <motion.div
          className="score-display"
          initial={{ scale: 0 }}
          animate={{ scale: 1 }}
          transition={{ delay: 0.4, type: "spring" }}
        >
          {displayScore} / {total}
        </motion.div>

        <div className="percentage">{percentage}%</div>

        <AnimatePresence mode="wait">
          {saveStatus === "saving" && (
            <motion.div
              key="saving"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="status saving"
            >
              Saving result...
            </motion.div>
          )}

          {saveStatus === "success" && (
            <motion.div
              key="success"
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              exit={{ opacity: 0 }}
              className="status success"
            >
              ✔ Result saved
            </motion.div>
          )}

          {saveStatus === "error" && (
            <motion.div
              key="error"
              initial={{ x: -10 }}
              animate={{ x: 0 }}
              exit={{ opacity: 0 }}
              className="status error"
            >
              ✖ Failed to save
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>

      <motion.button
        className="back-button"
        initial={{ opacity: 0, x: -30 }}
        animate={{ opacity: 1, x: 0 }}
        transition={{ delay: 0.6 }}
        whileHover={{ scale: 1.05, x: -5 }}
        whileTap={{ scale: 0.95 }}
        disabled={saveStatus === "saving"}
        onClick={() => navigate(`/quiz/${quizSlug}`)}
      >
        ← Back to Quiz
      </motion.button>
    </div>
  );
};

export default QuizResult;
