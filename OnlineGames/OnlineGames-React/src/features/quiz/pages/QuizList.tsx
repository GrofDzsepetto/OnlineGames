import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { getQuizzes, deleteQuiz } from "../services/quizService";
import QuizCard from "../components/QuizCard";
import { useAuth } from "../../../auth/AuthContext";
import { useTranslation } from "react-i18next";
import "../styles/QuizList.css";

import type { Quiz } from "../types/quiz";


const QuizList = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const { t } = useTranslation();

  const [quizzes, setQuizzes] = useState<Quiz[]>([]);
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const [selectedLanguage, setSelectedLanguage] = useState<string>("all");

  const load = async () => {
    try {
      const data: Quiz[] = await getQuizzes();
      setQuizzes(data ?? []);
    } catch (err) {
      console.error("getQuizzes error:", err);
      setQuizzes([]);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const filteredQuizzes =
    selectedLanguage === "all"
      ? quizzes
      : quizzes.filter(
          (q) =>
            q.language?.toLowerCase() ===
            selectedLanguage.toLowerCase()
        );

  const onDelete = async (quiz: Quiz) => {
    if (!quiz.id) return;

    const ok = window.confirm(
      `${t("quizList.deleteConfirm")}\n\n${quiz.title}`
    );
    if (!ok) return;

    try {
      setDeletingId(quiz.id);
      await deleteQuiz(quiz.id);
      setQuizzes((prev) =>
        prev.filter((q) => q.id !== quiz.id)
      );
    } catch (e: any) {
      alert(e?.message ?? t("quizList.deleteError"));
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="quizlist-container">
      {/* ================= HEADER ================= */}
      <div className="quizlist-header">
        <div className="quizlist-left">
          <select
            className="quiz-language-select"
            value={selectedLanguage}
            onChange={(e) =>
              setSelectedLanguage(e.target.value)
            }
          >
            <option value="all">
              🌍 {t("quizList.all")}
            </option>
            <option value="hu">🇭🇺 Magyar</option>
            <option value="en">🇬🇧 English</option>
          </select>
        </div>

        <div className="quizlist-center">
          <h2 className="quizlist-title">
            {t("quizList.title")}
          </h2>
        </div>

        <div className="quizlist-right">
          {user && (
            <Link
              to="/create-quiz"
              className="create-quiz-btn"
            >
              + {t("quizList.create")}
            </Link>
          )}
        </div>
      </div>

      {/* ================= QUIZ LIST ================= */}
      {filteredQuizzes.length > 0 ? (
        filteredQuizzes.map((quiz) => {
          const isOwner =
            user &&
            quiz.created_by?.toString() === user.id;

          return (
            <div
              key={quiz.id}
              className="quiz-wrapper"
            >
              {isOwner && (
                <div className="quiz-actions">
                  <button
                    className="settings-btn"
                    onClick={(e) => {
                      e.stopPropagation();
                      navigate(
                        `/edit-quiz/${quiz.id}`
                      );
                    }}
                  >
                    ✏
                  </button>

                  <button
                    className="delete-btn"
                    disabled={
                      deletingId === quiz.id
                    }
                    onClick={(e) => {
                      e.stopPropagation();
                      onDelete(quiz);
                    }}
                  >
                    {deletingId === quiz.id
                      ? "..."
                      : "🗑"}
                  </button>
                </div>
              )}

              <div
                onClick={() =>
                  navigate(`/quiz/${quiz.slug}`)
                }
                style={{ cursor: "pointer" }}
              >
                <QuizCard quiz={quiz} />
              </div>

              {quiz.creator_name && (
                <span className="creator-name">
                  {t("quizList.createdBy")}:{" "}
                  {quiz.creator_name}
                </span>
              )}
            </div>
          );
        })
      ) : (
        <p>{t("quizList.noQuizzes")}</p>
      )}
    </div>
  );
};

export default QuizList;
