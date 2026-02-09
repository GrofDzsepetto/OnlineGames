import { NavLink, useNavigate } from "react-router-dom";
import { useAuth } from "../../auth/AuthContext";

const Navbar = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  return (
     <nav style={{ display: "flex", justifyContent: "space-between" }}> 
      <div>
        <NavLink to="/" style={{ marginRight: "10px" }}>Home</NavLink>
        <NavLink to="/quizzes">Quiz</NavLink>
      </div>

      <div>
        {user ? (
          <div style={{ display: "flex", alignItems: "center", gap: "15px" }}>
            <span>Bejelentkezve: <strong>{user.name || user.email}</strong></span>
            <button 
              onClick={handleLogout} 
              style={{ 
                cursor: "pointer", 
                padding: "5px 10px", 
                backgroundColor: "#ff4d4d", 
                color: "white", 
                border: "none", 
                borderRadius: "4px" 
              }}
            >
              Logout
            </button>
          </div>
        ) : (
          <NavLink to="/login">Login</NavLink>
          
        )}
      </div>
    </nav>
  );
};

export default Navbar;