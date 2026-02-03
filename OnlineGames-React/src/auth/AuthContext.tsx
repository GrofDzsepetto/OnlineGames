import { createContext, useContext, useEffect, useState } from "react";

type User = {
  email: string;
  name: string;
};

type AuthContextType = {
  user: User | null;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);

const refreshUser = async () => {
  console.log("refreshUser() called");

  const res = await fetch("https://dzsepetto.hu/api/auth/user.php", {
    credentials: "include",
  });

  console.log("user.php status:", res.status);

  const data = await res.json();
  console.log("user.php response:", data);

  setUser(data.user ?? null);
};



  useEffect(() => {
    refreshUser();
  }, []);

  return (
    <AuthContext.Provider value={{ user, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
};
